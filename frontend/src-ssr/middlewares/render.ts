import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { TenantCacheService } from '../services/TenantCache';

const tenantService = TenantCacheService.getInstance();

export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  // Capture all unmatched routes and hand them over
  // to Vue and Vue Router for rendering
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    res.header('Content-Type', 'text/html');

    try {
      const host = req.hostname;
      const tenantConfig = (await tenantService).getTenantConfigByDomain(host);

      if (!tenantConfig) {
        res.status(404).send('Tenant Not Found');
        return;
      }

      // Attach only the tenant configuration to the request
      req.tenantConfig = tenantConfig;

      // Render the page using Vue SSR with the extracted tenant config
      const html = await render({ req, res });

      // Inject tenant config into `window.__TENANT_CONFIG__`
      const hydratedHtml = html.replace(
        '</body>',
        `<script>window.__TENANT_CONFIG__ = ${JSON.stringify(tenantConfig)};</script></body>`,
      );

      res.send(hydratedHtml);
    } catch (err) {
      const error = err as RenderError;

      // If an error specifies a redirect URL, follow it
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
