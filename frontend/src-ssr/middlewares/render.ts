import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { v4 as uuidv4 } from 'uuid';
import { TenantCacheService } from '../services/TenantCache';
import { TenantConfigProtected } from '../types/tenant.types';
import { createTenantConfigFromEnv, filterTenantConfig } from '../utils/tenantConfigUtil';
import { isValidHostname } from '../utils/validationUtil';

/**
 * SSR Middleware for rendering pages.
 * Gets the tenant config based on the hostname and attaches it to the request.
 * Injects the tenant config into the window object for use in the client.
 *
 * @param app - The express app instance.
 * @param resolve - The URL resolver instance.
 * @param render - The render function.
 * @param serve - The serve function.
 */
export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    res.header('Content-Type', 'text/html');

    try {
      let tenantConfig: TenantConfigProtected | null = null;
      const isMultiTenant = Boolean(process.env.VITE_MULTI_TENANT);

      if (isMultiTenant) {
        // Multi-tenant mode: Get tenant config based on hostname
        const host = String(req.hostname).split(':')[0] || '';

        if (!isValidHostname(host)) {
          // TODO: SSR Error pages
          res.status(400).send('Invalid Hostname');
          return;
        }

        tenantConfig = await (await TenantCacheService.getInstance()).getTenantConfigByDomain(host);

        if (!tenantConfig) {
          // TODO: SSR Error pages
          res.status(404).send('Tenant Not Found');
          return;
        }
      } else {
        // Single-tenant mode: Use environment variables
        tenantConfig = createTenantConfigFromEnv();
      }

      // Attach full tenantConfig to the request for SSR
      req.tenantConfig = tenantConfig;

      // Generate a trace ID for this request
      const traceId = uuidv4();
      const traceInfo = {
        id: traceId,
        timestamp: new Date().toISOString(),
        environment: process.env.NODE_ENV || 'development',
        tenant: tenantConfig.tenantId,
        runtime: 'server' as const,
      };

      // Attach trace info to the request for use in SSR
      req.__TRACE__ = traceInfo;

      // Filter non-public fields before injecting into window
      const publicTenantConfig = filterTenantConfig(tenantConfig);

      // Render the page using Vue SSR
      const html = await render({ req, res });

      // Inject tenant config and trace info into window
      const hydratedHtml = html.replace(
        '</body>',
        `<script>
          window.__TENANT_CONFIG__ = ${JSON.stringify(publicTenantConfig)};
          window.__TRACE__ = ${JSON.stringify(traceInfo)};
        </script></body>`,
      );

      res.send(hydratedHtml);
    } catch (err) {
      const error = err as RenderError;

      if (error.url) {
        res.redirect(error.code ?? 302, error.url);
        return;
      }

      if (error.code === 404) {
        res.status(404).send('404 | Page Not Found');
        return;
      }

      if (process.env.DEV ?? '') {
        serve.error({ err: error, req, res });
      } else {
        res.status(500).send('500 | Internal Server Error');

        if (process.env.DEBUGGING ?? '') {
          console.error(error.stack);
        }
      }
    }
  });
});
