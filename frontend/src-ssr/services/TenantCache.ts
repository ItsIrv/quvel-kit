import { createAxios } from 'src/modules/Core/utils/axiosUtil';
import { Tenant, TenantConfigProtected } from '../types/tenant.types';

export class TenantCacheService {
  private static readonly REFRESH_INTERVAL_MS = 1000 * 60;
  private static instance: TenantCacheService;
  private readonly tenants: Map<string, Tenant> = new Map();
  private readonly parents: Map<string, Tenant> = new Map();

  private constructor() {}

  /**
   * Get the singleton instance.
   */
  public static async getInstance(): Promise<TenantCacheService> {
    if (!this.instance) {
      this.instance = new TenantCacheService();

      await this.instance.loadTenants();

      // Refresh cache every minute
      setInterval(() => void this.instance.loadTenants(), this.REFRESH_INTERVAL_MS); // 1 minute
    }
    return this.instance;
  }

  /**
   * Load all tenants from API and store in memory.
   */
  private async loadTenants(): Promise<void> {
    try {
      // TODO: Remove tenant cache (dumps) endpoint.
      // Fetch tenants from API and cache as they are requested by hostname
      const response = await createAxios().get<{
        data: (Tenant & { config: TenantConfigProtected & { frontendUrl: string } })[];
      }>(
        // 'http://quvel-app:8000/tenant/cache'
        'https://api.quvel.127.0.0.1.nip.io/tenant/cache',
      );

      if (!Array.isArray(response.data.data)) {
        return;
      }

      response.data.data.forEach((tenant) => {
        if (tenant.config) {
          const tenantConfig = tenant.config;
          // appUrl should refer to the frontend url in frontend context
          // in backend context appUrl refers to the backend url
          tenantConfig.apiUrl = tenant.config.appUrl;
          tenantConfig.appUrl = tenant.config.frontendUrl;
          tenantConfig.tenantId = tenant.id;
          tenantConfig.tenantName = tenant.name;

          if (!tenantConfig.__visibility) {
            tenantConfig.__visibility = {};
          }

          // the backend doesnt keep track of these overwrites
          tenantConfig.__visibility.apiUrl = 'public';
          tenantConfig.__visibility.tenantId = 'public';
          tenantConfig.__visibility.tenantName = 'public';
        }

        this.tenants.set(tenant.domain, tenant);

        if (!tenant.parent_id) {
          this.parents.set(tenant.id, tenant);
        }
      });
    } catch (error) {
      console.error('Failed to load tenants:', error);
    }
  }

  /**
   * Find a tenant's config by domain. Always returns the parent domain config if available.
   */
  public getTenantConfigByDomain(domain: string): TenantConfigProtected | null {
    const tenant = this.tenants.get(domain);

    if (!tenant) {
      return null;
    }

    if (tenant.parent_id) {
      const parentTenant = this.parents.get(tenant.parent_id);

      if (!parentTenant) {
        return null;
      }

      return parentTenant.config;
    }

    return tenant.config;
  }
}
