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
      const response = await createAxios().get<{
        data: (Tenant & { config: TenantConfigProtected })[];
      }>(
        // 'http://quvel-app:8000/tenant/cache'
        'https://api.quvel.127.0.0.1.nip.io/tenant/cache',
      );

      if (!Array.isArray(response.data.data)) {
        return;
      }

      response.data.data.forEach((tenant) => {
        // Ensure config is properly formatted
        const formattedConfig: TenantConfigProtected = {
          api_url: tenant.config.api_url ?? '',
          app_url: tenant.config.app_url ?? '',
          app_name: tenant.config.app_name ?? '',
          internal_api_url: tenant.config.internal_api_url ?? '',
          __visibility: tenant.config.__visibility ?? {},
          tenant_id: tenant.id ?? '',
          tenant_name: tenant.name ?? '',
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
  public getTenantConfigByDomain(domain: string): TenantConfigProtected | null {
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
