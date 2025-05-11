import { Express, RequestHandler } from 'express';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';
import compression from 'compression';
import cors from 'cors';

interface ConfigureExpressOptions {
  enableCompression?: boolean;
  enableCors?: boolean;
  corsOrigins?: string[];
  rateLimit?:
    | {
        windowMs?: number;
        max?: number;
      }
    | undefined;
  trustProxy?: boolean;
  strictGetOnly?: boolean;
}

export function configureExpress(app: Express, options: ConfigureExpressOptions = {}): void {
  // Hide x-powered-by
  app.disable('x-powered-by');

  // Trust proxy
  if (options.trustProxy) {
    app.set('trust proxy', 1);
  }

  // Compression (optional)
  if (options.enableCompression) {
    app.use(compression() as unknown as RequestHandler);
  }

  // Helmet with CSP and clickjacking protection
  helmet({
    contentSecurityPolicy: {
      useDefaults: true,
      directives: {
        frameAncestors: ["'none'"],
      },
    },
    frameguard: {
      action: 'deny',
    },
    referrerPolicy: {
      policy: 'no-referrer',
    },
    crossOriginEmbedderPolicy: false, // SSR dev mode will fail if true
  });

  // Optional CORS
  if (options.enableCors) {
    app.use(
      cors({
        origin: options.corsOrigins ?? [String(process.env.VITE_APP_URL)],
        credentials: true,
      }),
    );
  }

  // Restrict to GET only
  if (options.strictGetOnly ?? true) {
    app.use((req, res, next) => {
      if (req.method !== 'GET') {
        res.status(405).json({ error: 'Method Not Allowed' });
      } else {
        next();
      }
    });
  }

  // Rate limiting
  const rateLimitWindowMs = options.rateLimit?.windowMs ?? 60_000;
  const rateLimitMax = options.rateLimit?.max ?? 100;

  if (options.rateLimit) {
    app.use(
      rateLimit({
        windowMs: rateLimitWindowMs,
        max: rateLimitMax,
        standardHeaders: true,
        legacyHeaders: false,
      }),
    );
  }
}
