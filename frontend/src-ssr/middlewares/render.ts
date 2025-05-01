import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { TenantCacheService } from '../services/TenantCache';
import { TenantConfigProtected, TenantConfigVisibilityRecord } from '../types/tenant.types';

const tenantService = TenantCacheService.getInstance();

/**
 * Creates a tenant config object from environment variables.
 * Used in single-tenant mode when VITE_MULTI_TENANT is false.
 */
function createTenantConfigFromEnv(): TenantConfigProtected {
  return {
    apiUrl: process.env.VITE_API_URL || '',
    appUrl: process.env.VITE_APP_URL || '',
    appName: process.env.VITE_APP_NAME || '',
    internalApiUrl: process.env.VITE_INTERNAL_API_URL || '',
    tenantId: process.env.VITE_TENANT_ID || '',
    tenantName: process.env.VITE_TENANT_NAME || '',
    pusherAppKey: process.env.VITE_PUSHER_APP_KEY || '',
    pusherAppCluster: process.env.VITE_PUSHER_APP_CLUSTER || '',
    socialiteProviders: (process.env.VITE_SOCIALITE_PROVIDERS || '').split(',').filter(Boolean),
    __visibility: {
      apiUrl: 'public',
      appUrl: 'public',
      appName: 'public',
      tenantId: 'public',
      tenantName: 'public',
      pusherAppKey: 'public',
      pusherAppCluster: 'public',
      socialiteProviders: 'public',
    },
  };
}

/**
 * Filters out non-public fields from the tenant config.
 */
function filterTenantConfig(config: TenantConfigProtected): Partial<TenantConfigProtected> {
  const publicConfig: Partial<TenantConfigProtected> = {};

  Object.keys(config.__visibility).forEach((key) => {
    const typedKey = key as keyof TenantConfigVisibilityRecord;

    if (config.__visibility?.[typedKey] === 'public') {
      const value = config[typedKey];

      if (typeof value === 'string' || Array.isArray(value)) {
        publicConfig[typedKey] = value as string & string[];
      }
    }
  });

  return publicConfig;
}

export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    res.header('Content-Type', 'text/html');

    try {
      let tenantConfig: TenantConfigProtected | null = null;
      const isMultiTenant = Boolean(process.env.VITE_MULTI_TENANT);

      if (isMultiTenant) {
        // Multi-tenant mode: Get tenant config based on hostname
        const host = String(req.hostname).match(/[^:]{0,50}/)?.[0] ?? '';
        tenantConfig = (await tenantService).getTenantConfigByDomain(host);

        if (!tenantConfig) {
          res.status(404).send('Tenant Not Found');
          return;
        }
      } else {
        // Single-tenant mode: Use environment variables
        tenantConfig = createTenantConfigFromEnv();
      }

      // Attach full tenantConfig to the request for SSR
      req.tenantConfig = tenantConfig;

      // Filter non-public fields before injecting into window
      const publicTenantConfig = filterTenantConfig(tenantConfig);

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
