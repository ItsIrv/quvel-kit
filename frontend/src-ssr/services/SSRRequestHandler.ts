import type { Request, Response } from 'express';
import { v4 as uuidv4 } from 'uuid';
import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRSingletonService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import { SSRAssetInjectionService } from './SSRAssetInjectionService';
import { AppConfigProtected, TenantConfigProtected } from '../types/tenant.types';
import {
  createAppConfigFromEnv,
  createTenantConfigFromEnv,
  filterConfig,
} from '../utils/configUtil';
import { isValidHostname } from '../utils/validationUtil';
import { TenantResolver } from './TenantResolver';
import type { TraceInfo } from 'src/modules/Core/types/logging.types';
import type { SSRRequestContext } from '../ssr.d';

export class SSRRequestHandler extends SSRService implements SSRSingletonService {
  private tenantResolver!: TenantResolver;
  private logger!: SSRLogService;
  private container!: SSRServiceContainer;

  override register(container: SSRServiceContainer): void {
    this.container = container;
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
      res.header('X-Trace-Id', context.traceInfo.id);

      this.logger.debug('SSR request started', {
        url: req.url,
        method: req.method,
        userAgent: req.get('user-agent'),
      });

      // Resolve app configuration
      context.appConfig = await this.resolveTenant(req);

      // Update trace info with tenant (only if tenant config)
      if (context.appConfig && 'tenantId' in context.appConfig) {
        context.traceInfo.tenant = context.appConfig.tenantId;
      }

      // Create a scoped asset injection service for this request
      const assetInjectionService = this.container.scoped(SSRAssetInjectionService, { req, res });

      // Set tenant assets if available
      if (context.appConfig?.assets) {
        assetInjectionService.setTenantAssets(context.appConfig.assets);
      }

      req.requestContext = context;

      // Filter non-public fields before injecting into window
      const publicConfig = context.appConfig ? filterConfig(context.appConfig) : null;

      if (!publicConfig) {
        this.logger.error('No config found', { domain: req.get('host') });

        throw new Error('No config found');
      }

      // Render the application
      const startRender = Date.now();
      const html = await renderFn({ req, res });
      const renderDuration = Date.now() - startRender;

      // Change runtime to client for browser
      const clientTraceInfo = { ...context.traceInfo, runtime: 'client' as const };

      // Inject app config and trace info into window
      const scriptTag = `<script>
          window.__APP_CONFIG__ = ${JSON.stringify(publicConfig)};
          window.__TRACE__ = ${JSON.stringify(clientTraceInfo)};
        </script>`;

      // Inject before title tag (similar to Quasar's __INITIAL_STATE__)
      let hydratedHtml = html.includes('<title>')
        ? html.replace('<title>', `${scriptTag}<title>`)
        : html.replace('<head>', `<head>${scriptTag}`);

      // Process HTML to inject tenant assets
      hydratedHtml = assetInjectionService.processHTML(hydratedHtml);

      const duration = Date.now() - context.startTime;
      const statusCode = res.statusCode || 200;

      this.logger.info('SSR request completed', {
        duration,
        statusCode,
        htmlSize: hydratedHtml.length,
        renderDuration,
      });

      res.send(hydratedHtml);
    } catch (error) {
      const duration = Date.now() - context.startTime;

      this.logger.error('SSR request failed', {
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

    const traceInfo: TraceInfo = {
      id: traceId,
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV || 'development',
      runtime: 'server',
    };

    return {
      startTime,
      appConfig: null,
      traceInfo,
    };
  }

  private async resolveTenant(
    req: Request,
  ): Promise<AppConfigProtected | TenantConfigProtected | null> {
    const isMultiTenant = process.env.SSR_MULTI_TENANT === 'true';

    if (!isMultiTenant) {
      // Single-tenant mode - check if we need tenant fields from env
      const hasTenantEnvVars = process.env.VITE_TENANT_ID || process.env.VITE_TENANT_NAME;

      if (hasTenantEnvVars) {
        // Use tenant config if tenant env vars are provided
        const tenantConfig = createTenantConfigFromEnv();
        this.logger.debug('Single-tenant mode with tenant fields', {
          tenantId: tenantConfig.tenantId,
        });
        return tenantConfig;
      } else {
        // Use app config for pure single-tenant mode
        const appConfig = createAppConfigFromEnv();
        this.logger.debug('Single-tenant mode without tenant fields');
        return appConfig;
      }
    }

    // Multi-tenant mode
    const host = req.get('host');
    if (!host || !isValidHostname(host)) {
      this.logger.warning('Invalid or missing hostname', { host });
      return null;
    }

    this.logger.debug('Resolving tenant for hostname', { host });

    const tenantConfig = await this.tenantResolver.getTenantConfigByDomain(host);

    if (!tenantConfig) {
      this.logger.warning('Tenant not found for hostname', { host });
      return null;
    }

    this.logger.debug('Tenant resolved successfully', {
      host,
      tenantId: tenantConfig.tenantId,
      tenantName: tenantConfig.tenantName,
    });

    return tenantConfig;
  }
}
