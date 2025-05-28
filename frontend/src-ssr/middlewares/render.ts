import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { v4 as uuidv4 } from 'uuid';
import { ServiceContainer } from '../services/ServiceContainer';
import { TenantConfigProtected } from '../types/tenant.types';
import { createTenantConfigFromEnv, filterTenantConfig } from '../utils/tenantConfigUtil';
import { isValidHostname } from '../utils/validationUtil';
import { TraceInfo } from 'src/modules/Core/types/logging.types';

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
    const traceId = uuidv4();
    const startTime = Date.now();
    
    res.header('Content-Type', 'text/html');

    try {
      const container = await ServiceContainer.getInstance();
      
      // Create trace info for this request
      const traceInfo: TraceInfo = {
        id: traceId,
        timestamp: new Date().toISOString(),
        environment: process.env.NODE_ENV || 'development',
        runtime: 'server' as TraceInfo['runtime'],
      };
      
      // Create request-specific logger
      const logger = container.createLogger(traceInfo);
      
      logger.debug('SSR request started', {
        url: req.url,
        method: req.method,
        userAgent: req.get('user-agent'),
      });

      let tenantConfig: TenantConfigProtected | null = null;
      const isMultiTenant = Boolean(process.env.VITE_MULTI_TENANT);

      if (isMultiTenant) {
        // Multi-tenant mode: Get tenant config based on hostname
        const host = String(req.hostname).split(':')[0] || '';

        if (!isValidHostname(host)) {
          logger.warning('Invalid hostname for multi-tenant request', { host });
          res.status(400).send('Invalid Hostname');
          return;
        }

        tenantConfig = await container.tenantResolver.getTenantConfigByDomain(host);

        if (!tenantConfig) {
          logger.warning('Tenant not found for hostname', { host });
          res.status(404).send('Tenant Not Found');
          return;
        }
        
        logger.debug('Tenant resolved', { 
          host, 
          tenantId: tenantConfig.tenantId,
          tenantName: tenantConfig.tenantName,
        });
      } else {
        // Single-tenant mode: Use environment variables
        tenantConfig = createTenantConfigFromEnv();
        logger.debug('Single-tenant mode', { tenantId: tenantConfig.tenantId });
      }

      // Update trace info with tenant
      traceInfo.tenant = tenantConfig.tenantId;

      // Attach full tenantConfig to the request for SSR
      req.tenantConfig = tenantConfig;

      // Attach trace info to the request for use in SSR
      req.traceInfo = traceInfo;

      // Filter non-public fields before injecting into window
      const publicTenantConfig = filterTenantConfig(tenantConfig);

      logger.debug('Rendering Vue application');

      // Render the page using Vue SSR
      const html = await render({ req, res });

      // Change runtime to client
      traceInfo.runtime = 'client';

      // Inject tenant config and trace info into window
      const hydratedHtml = html.replace(
        '</body>',
        `<script>
          window.__TENANT_CONFIG__ = ${JSON.stringify(publicTenantConfig)};
          window.__TRACE__ = ${JSON.stringify(traceInfo)};
        </script></body>`,
      );

      const duration = Date.now() - startTime;
      logger.info('SSR request completed', {
        duration,
        statusCode: res.statusCode,
        htmlSize: hydratedHtml.length,
      });

      res.send(hydratedHtml);
    } catch (err) {
      const error = err as RenderError;
      const duration = Date.now() - startTime;
      
      // Try to get logger, fall back to console if not available
      try {
        const container = await ServiceContainer.getInstance();
        const logger = container.createLogger({
          id: traceId,
          timestamp: new Date().toISOString(),
          environment: process.env.NODE_ENV || 'development',
          runtime: 'server',
        });
        
        logger.error('SSR request failed', {
          duration,
          error: error.message || 'Unknown error',
          stack: error.stack,
          url: req.url,
        });
      } catch {
        console.error('SSR request failed:', error);
      }

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
