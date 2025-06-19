import { SSRService } from './SSRService';

/**
 * SSR Endpoint Configuration Service
 *
 * Provides configurable backend endpoint URLs for SSR services.
 * Supports environment-based configuration and centralized endpoint management.
 */
export class SSREndpointConfigService extends SSRService {
  private readonly baseUrl: string;
  private readonly tenantPrefix: string;

  constructor() {
    super();
    this.baseUrl = this.getBaseUrl();
    this.tenantPrefix = this.getTenantPrefixValue();
  }

  /**
   * Get the base API URL for backend requests.
   */
  private getBaseUrl(): string {
    const baseUrl = process.env.SSR_TENANT_SSR_API_URL || process.env.VITE_API_URL || '';
    return baseUrl.replace(/\/$/, ''); // Remove trailing slash
  }

  /**
   * Get the tenant endpoint prefix value.
   */
  private getTenantPrefixValue(): string {
    return process.env.SSR_TENANT_ENDPOINT_PREFIX || 'tenant-info';
  }

  /**
   * Get the current tenant endpoint URL.
   * Used by SSR to get current tenant info.
   */
  getTenantProtectedEndpoint(): string {
    return `${this.baseUrl}/${this.tenantPrefix}/protected`;
  }

  /**
   * Get the tenant cache endpoint URL.
   * Used by SSR to get all tenants for caching.
   */
  getTenantCacheEndpoint(): string {
    return `${this.baseUrl}/${this.tenantPrefix}/cache`;
  }

  /**
   * Get the base URL without tenant prefix.
   * Useful for other API endpoints.
   */
  getBaseApiUrl(): string {
    return this.baseUrl;
  }

  /**
   * Build a complete URL for a given endpoint path.
   */
  buildUrl(path: string): string {
    const cleanPath = path.replace(/^\//, ''); // Remove leading slash
    return `${this.baseUrl}/${cleanPath}`;
  }

  /**
   * Build a tenant-namespaced URL for a given path.
   */
  buildTenantUrl(path: string): string {
    const cleanPath = path.replace(/^\//, ''); // Remove leading slash
    return `${this.baseUrl}/${this.tenantPrefix}/${cleanPath}`;
  }
}
