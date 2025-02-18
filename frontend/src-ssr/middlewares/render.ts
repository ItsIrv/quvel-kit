import { type FastifyRequest, type FastifyReply } from 'fastify'
import { type RenderError } from '#q-app'
import { defineSsrMiddleware } from '#q-app/wrappers'

// This middleware should execute last
// since it captures all unmatched routes and
// renders the page with Vue

export default defineSsrMiddleware(({ app, resolve, render, serve }) => {
  // Capture all unmatched routes and hand them over
  // to Vue and Vue Router for rendering
  app.get(resolve.urlPath('*'), async (req: FastifyRequest, reply: FastifyReply) => {
    reply.header('Content-Type', 'text/html')

    try {
      // Render the page using Vue SSR
      /** @ts-expect-error quasar ssr middleware expects express, but this will work */
      const html = await render({ req, res: reply })
      reply.send(html)
    } catch (err) {
      const error = err as RenderError

      // If an error specifies a redirect URL, follow it
      if (error.url) {
        reply.redirect(error.url, error.code ?? 302)
        return
      }

      if (error.code === 404) {
        // If Vue Router couldn't find the requested route,
        // return a 404 response
        reply.status(404).send('404 | Page Not Found')
        return
      }

      if (process.env.DEV) {
        // In development mode, use Quasar CLI's built-in error page
        /** @ts-expect-error quasar ssr middleware expects express, but this will work */
        serve.error({ err: error, req, res: reply })
      } else {
        // In production, return a generic error message
        reply.status(500).send('500 | Internal Server Error')

        if (process.env.DEBUGGING) {
          console.error(error.stack)
        }
      }
    }
  })
})
