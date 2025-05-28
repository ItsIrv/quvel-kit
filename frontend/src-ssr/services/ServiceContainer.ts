import { AxiosInstance } from 'axios';
import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';
import { TenantConfigProtected } from '../types/tenant.types';
import { createAxios } from '../utils/createAxios';
import { createSSRLogger } from './Logger';

export interface ITenantResolver {
  getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null>;
}

export class ServiceContainer {
  private static instance: ServiceContainer;
  private _tenantResolver?: ITenantResolver;
  private _httpClient?: AxiosInstance;
  private _logger?: LoggerInterface;

  static async getInstance(): Promise<ServiceContainer> {
    if (!ServiceContainer.instance) {
      ServiceContainer.instance = new ServiceContainer();
      await ServiceContainer.instance.initialize();
    }
    return ServiceContainer.instance;
  }

  get tenantResolver(): ITenantResolver {
    if (!this._tenantResolver) {
      throw new Error('TenantResolver not initialized');
    }
    return this._tenantResolver;
  }

  get httpClient(): AxiosInstance {
    if (!this._httpClient) {
      throw new Error('HttpClient not initialized');
    }
    return this._httpClient;
  }

  get logger(): LoggerInterface {
    if (!this._logger) {
      throw new Error('Logger not initialized');
    }
    return this._logger;
  }

  createLogger(traceInfo: TraceInfo): LoggerInterface {
    return createSSRLogger(traceInfo);
  }

  private async initialize(): Promise<void> {
    // Initialize HTTP client
    this._httpClient = createAxios();

    // Initialize default logger (without trace info initially)
    this._logger = createSSRLogger({
      id: 'bootstrap',
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV || 'development',
      runtime: 'server',
    });

    this._logger.info('SSR Service Container initializing');

    // Initialize tenant resolver (importing here to avoid circular deps)
    const { TenantCacheService } = await import('./TenantCache');
    this._tenantResolver = await TenantCacheService.getInstance();

    this._logger.info('SSR Service Container initialized');
  }
}
