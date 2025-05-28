import { AxiosInstance } from 'axios';
import { TenantConfigProtected } from '../types/tenant.types';
import { createAxios } from '../utils/createAxios';

export interface ITenantResolver {
  getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null>;
}

export class ServiceContainer {
  private static instance: ServiceContainer;
  private _tenantResolver?: ITenantResolver;
  private _httpClient?: AxiosInstance;

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

  private async initialize(): Promise<void> {
    // Initialize HTTP client
    this._httpClient = createAxios();

    // Initialize tenant resolver (importing here to avoid circular deps)
    const { TenantCacheService } = await import('./TenantCache');
    this._tenantResolver = await TenantCacheService.getInstance();
  }
}
