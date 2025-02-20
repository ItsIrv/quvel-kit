import { type RenderError } from '#q-app';
import { defineSsrMiddleware } from '#q-app/wrappers';
import { Request, Response } from 'express';

// This middleware should execute last
// since it captures all unmatched routes and
// renders the page with Vue

export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  // Capture all unmatched routes and hand them over
  // to Vue and Vue Router for rendering
  app.get(resolve.urlPath('*'), async (req: Request, res: Response) => {
    res.header('Content-Type', 'text/html');

    try {
      // Render the page using Vue SSR
      const html = await render({ req, res });
      res.send(html);
    } catch (err) {
      const error = err as RenderError;

      // If an error specifies a redirect URL, follow it
      if (error.url) {
        res.redirect(error.code ?? 302, error.url);

        return;
      }

      if (error.code === 404) {
        // If Vue Router couldn't find the requested route,
        // return a 404 response
        res.status(404).send('404 | Page Not Found');
        return;
      }

      if (process.env.DEV ?? '') {
        // In development mode, use Quasar CLI's built-in error page
        serve.error({ err: error, req, res });
      } else {
        // In production, return a generic error message
        res.status(500).send('500 | Internal Server Error');

        if (process.env.DEBUGGING ?? '') {
          console.error(error.stack);
        }
      }
    }
  });
});
