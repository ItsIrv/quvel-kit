import { createAxios } from 'src/utils/axiosUtil';
import { Tenant, TenantConfig } from '../types/tenant.types';

export class TenantCacheService {
  private static instance: TenantCacheService;
  private tenants: Map<string, Tenant> = new Map();
  private parents: Map<string, Tenant> = new Map();

  private constructor() {}

  /**
   * Get the singleton instance.
   */
  public static async getInstance(): Promise<TenantCacheService> {
    if (!this.instance) {
      this.instance = new TenantCacheService();

      await this.instance.loadTenants();

      // Refresh cache every minute
      setInterval(() => void this.instance.loadTenants(), 1000 * 60 * 1); // 1 minute
    }
    return this.instance;
  }

  /**
   * Load all tenants from API and store in memory.
   */
  private async loadTenants(): Promise<void> {
    try {
      const response = await createAxios().get<{
        data: (Tenant & { config: TenantConfig })[];
      }>('http://quvel-app:8000/tenant/cache');

      response.data.data.forEach((tenant) => {
        // Ensure config is properly formatted
        const formattedConfig: TenantConfig = {
          api_url: tenant.config.api_url ?? '',
          app_url: tenant.config.app_url ?? '',
          app_name: tenant.config.app_name ?? '',
          internal_api_url: tenant.config.internal_api_url ?? '',
          __visibility: tenant.config.__visibility ?? {},
        };

        // Store tenant with formatted config
        this.tenants.set(tenant.domain, {
          ...tenant,
          config: formattedConfig,
        });

        // Store parent tenant mapping
        if (!tenant.parent_id) {
          this.parents.set(tenant.id.toString(), {
            ...tenant,
            config: formattedConfig,
          });
        }
      });
    } catch (error) {
      console.error('Failed to load tenants:', error);
    }
  }

  /**
   * Find a tenant's config by domain. Always returns the parent domain config if available.
   */
  public getTenantConfigByDomain(domain: string): TenantConfig | null {
    const tenant = this.tenants.get(domain);

    if (!tenant) {
      return null;
    }

    if (tenant.parent_id) {
      const parentTenant = this.parents.get(tenant.parent_id.toString());
      return parentTenant?.config ?? tenant.config ?? null;
    }

    return tenant.config ?? null;
  }
}
