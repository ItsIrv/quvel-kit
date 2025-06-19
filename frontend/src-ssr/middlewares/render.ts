import { defineSsrMiddleware } from '#q-app/wrappers';
import type { Request, Response } from 'express';
import { getSSRContainer } from '../utils/ssrContainerSingleton';
import { SSRRequestHandler } from '../services/SSRRequestHandler';
import { RenderError } from '@quasar/app-vite';

/**
 * Slim SSR Middleware - delegates all logic to SSRRequestHandler service.
 */
export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    try {
      // Use the global singleton container
      const container = getSSRContainer();
      const handler = container.get(SSRRequestHandler);

      await handler.handleRequest(req, res, render);
    } catch (error) {
      // Fallback error handling if service resolution fails
      console.error('SSR service resolution failed:', error);

      if (process.env.DEV) {
        serve.error({ err: error as RenderError, req, res });
      } else {
        res.status(500).send('500 | Internal Server Error');
      }
    }
  });
});
