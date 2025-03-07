import { createAxios } from 'src/utils/axiosUtil';
import { Tenant, TenantConfig, TenantConfigResponse } from '../types/tenant';

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
    }
    return this.instance;
  }

  /**
   * Load all tenants from API and store in memory.
   */
  private async loadTenants(): Promise<void> {
    try {
      const response = await createAxios().get<{
        data: (Tenant & { config: TenantConfigResponse })[];
      }>('http://quvel-app:8000/tenant/cache');

      response.data.data.forEach((tenant) => {
        // Ensure config is properly formatted
        const formattedConfig: TenantConfig = {
          apiUrl: tenant.config.api_url ?? '',
          appUrl: tenant.config.app_url ?? '',
          appName: tenant.config.app_name ?? '',
          appEnv: tenant.config.app_env ?? '',
          internalApiUrl: tenant.config.internal_api_url ?? '',
          debug: tenant.config.debug ?? false,
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

  /**
   * Get all tenants.
   */
  public getAllTenants(): Tenant[] {
    return Array.from(this.tenants.values());
  }
}
