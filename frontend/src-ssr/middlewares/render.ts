import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { TenantCacheService } from '../services/TenantCache';
import { TenantConfigProtected } from '../types/tenant.types';

const tenantService = TenantCacheService.getInstance();

/**
 * Filters out non-public fields from the tenant config.
 */
function filterTenantConfig(config: TenantConfigProtected): Partial<TenantConfigProtected> {
  const publicConfig: Partial<TenantConfigProtected> = {};

  if (config.__visibility) {
    Object.keys(config.__visibility).forEach((key) => {
      const typedKey = key as keyof TenantConfigProtected;

      if (config.__visibility![typedKey] === 'public') {
        const value = config[typedKey];

        if (typeof value === 'string') {
          publicConfig[typedKey] = value;
        }
      }
    });
  }

  return publicConfig;
}

export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    res.header('Content-Type', 'text/html');

    try {
      const host = req.hostname;
      const tenantConfig = (await tenantService).getTenantConfigByDomain(host);

      if (!tenantConfig) {
        res.status(404).send('Tenant Not Found');
        return;
      }

      // Attach full tenantConfig to the request for SSR
      req.tenantConfig = tenantConfig;

      // Filter non-public fields before injecting into window
      const publicTenantConfig = filterTenantConfig(tenantConfig);

      // Add tenant_id and tenant_name
      publicTenantConfig.tenantId = tenantConfig.tenantId;
      publicTenantConfig.tenantName = tenantConfig.tenantName;

      // Render the page using Vue SSR
      const html = await render({ req, res });

      // Inject only public fields into `window.__TENANT_CONFIG__`
      const hydratedHtml = html.replace(
        '</body>',
        `<script>window.__TENANT_CONFIG__ = ${JSON.stringify(publicTenantConfig)};</script></body>`,
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
