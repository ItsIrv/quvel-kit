import type { Request, Response } from 'express';
import { v4 as uuidv4 } from 'uuid';
import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRRegisterService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import { TenantConfigProtected } from '../types/tenant.types';
import { createTenantConfigFromEnv, filterTenantConfig } from '../utils/tenantConfigUtil';
import { isValidHostname } from '../utils/validationUtil';
import { TenantResolver } from './TenantResolver';

export interface SSRRequestContext {
  traceId: string;
  startTime: number;
  logger: SSRLogService;
  tenantConfig: TenantConfigProtected | null;
}

export class SSRRequestHandler extends SSRService implements SSRRegisterService {
  private tenantResolver!: TenantResolver;
  private logger!: SSRLogService;

  override register(container: SSRServiceContainer): void {
    this.tenantResolver = container.get(TenantResolver);
    this.logger = container.get(SSRLogService);
  }

  async handleRequest(
    req: Request,
    res: Response,
    renderFn: (options: { req: Request; res: Response }) => Promise<string>,
  ): Promise<void> {
    const context = this.createRequestContext();

    try {
      res.header('Content-Type', 'text/html');
      res.header('X-Trace-Id', context.traceId);

      context.logger.debug('SSR request started', {
        url: req.url,
        method: req.method,
        userAgent: req.get('user-agent'),
      });

      // Resolve tenant configuration
      context.tenantConfig = await this.resolveTenant(req, context.logger);

      // Attach tenant config to request for Vue app access
      if (context.tenantConfig) {
        (req as unknown as { tenantConfig: TenantConfigProtected }).tenantConfig =
          context.tenantConfig;
      }

      // Render the application
      const startRender = Date.now();
      const html = await renderFn({ req, res });
      const renderDuration = Date.now() - startRender;

      const duration = Date.now() - context.startTime;
      const statusCode = res.statusCode || 200;

      context.logger.info('SSR request completed', {
        duration,
        statusCode,
        htmlSize: html.length,
        renderDuration,
      });

      res.send(html);
    } catch (error) {
      const duration = Date.now() - context.startTime;

      context.logger.error('SSR request failed', {
        duration,
        error: error instanceof Error ? error.message : 'Unknown error',
        stack: error instanceof Error ? error.stack : undefined,
      });

      res.status(500).send('500 | Internal Server Error');
    }
  }

  private createRequestContext(): SSRRequestContext {
    const traceId = uuidv4();
    const startTime = Date.now();

    // For SSR, we'll use the main logger instance
    const logger = this.logger;

    return {
      traceId,
      startTime,
      logger,
      tenantConfig: null,
    };
  }

  private async resolveTenant(
    req: Request,
    logger: SSRLogService,
  ): Promise<TenantConfigProtected | null> {
    const isMultiTenant = Boolean(process.env.VITE_MULTI_TENANT);

    if (!isMultiTenant) {
      // Single-tenant mode
      const tenantConfig = createTenantConfigFromEnv();
      logger.debug('Single-tenant mode', { tenantId: tenantConfig.tenantId });
      return tenantConfig;
    }

    // Multi-tenant mode
    const host = req.get('host');
    if (!host || !isValidHostname(host)) {
      logger.warning('Invalid or missing hostname', { host });
      return null;
    }

    logger.debug('Resolving tenant for hostname', { host });

    const tenantConfig = await this.tenantResolver.getTenantConfigByDomain(host);

    if (!tenantConfig) {
      logger.warning('Tenant not found for hostname', { host });
      return null;
    }

    // Filter the config based on visibility rules
    const filteredConfig = filterTenantConfig(tenantConfig);

    logger.debug('Tenant resolved successfully', {
      host,
      tenantId: filteredConfig.tenantId,
      tenantName: filteredConfig.tenantName,
    });

    return filteredConfig;
  }
}
