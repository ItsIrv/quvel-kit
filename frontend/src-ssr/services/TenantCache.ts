import { AxiosError, AxiosInstance } from 'axios';
import { CachedTenantConfig, Tenant, TenantConfigProtected } from '../types/tenant.types';
import { createAxios } from '../utils/createAxios';

/**
 * Service for caching tenant configurations.
 */
export class TenantCacheService {
  private static instance: TenantCacheService;
  private readonly preloadMode: boolean;
  private readonly resolverTtl: number;
  private readonly cacheTtl: number;
  private readonly intervalId: NodeJS.Timeout | null = null;
  private readonly domainCache = new Map<string, CachedTenantConfig>();
  private readonly tenantMap = new Map<string, Tenant>();
  private readonly parentMap = new Map<string, Tenant>();
  private readonly axiosInstance: AxiosInstance;

  private constructor() {
    this.preloadMode = Boolean(process.env.SSR_TENANT_SSR_PRELOAD_TENANTS);
    this.resolverTtl = Number(process.env.SSR_TENANT_SSR_RESOLVER_TTL) || 60 * 5;
    this.cacheTtl = Number(process.env.SSR_TENANT_SSR_CACHE_TTL) || 60 * 5;
    this.axiosInstance = createAxios();

    if (this.shouldUseCache()) {
      this.intervalId = setInterval(() => void this.loadAllTenants(), this.cacheTtl * 1000);
    }
  }
  private shouldUseCache(): boolean {
    return Boolean(process.env.SSR_ENABLE_CACHE) && this.preloadMode;
  }

  /**
   * Gets the singleton instance of the service.
   * @returns The singleton instance.
   */
  public static async getInstance(): Promise<TenantCacheService> {
    if (!this.instance) {
      this.instance = new TenantCacheService();

      if (this.instance.shouldUseCache()) {
        await this.instance.loadAllTenants();
      }
    }

    return this.instance;
  }

  /**
   * Retrieves the tenant configuration by domain.
   * @param domain - The domain of the tenant.
   * @returns The tenant configuration or null if not found.
   */
  public async getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null> {
    if (this.shouldUseCache()) {
      const tenant = this.tenantMap.get(domain);

      if (!tenant?.config) return null;

      const parent = this.getParentTenant(tenant);

      return this.normalizeConfig(parent);
    }

    const now = Date.now();
    const cached = this.domainCache.get(domain);

    if (cached && cached.expiresAt > now && this.shouldUseCache()) {
      return cached.config;
    }

    try {
      const response = await this.axiosInstance.get<{
        data: Tenant;
      }>(`${process.env.SSR_TENANT_SSR_API_URL}/tenant`, {
        headers: {
          'X-Tenant-Domain': domain,
        },
      });

      const tenant = response.data.data;

      if (!tenant?.config) return null;

      const config = this.normalizeConfig(tenant);

      this.domainCache.set(domain, {
        config,
        expiresAt: now + this.resolverTtl * 1000,
      });

      return config;
    } catch (e) {
      console.error(
        `[TenantCacheService] Failed to fetch tenant [${domain}]`,
        (e as AxiosError).response?.data,
      );

      return null;
    }
  }

  /**
   * Loads all tenants from the API and caches them.
   */
  public async loadAllTenants(): Promise<void> {
    try {
      const response = await this.axiosInstance.get<{
        data: Tenant[];
      }>(`${process.env.SSR_TENANT_SSR_API_URL}/tenant/cache`);

      this.tenantMap.clear();
      this.parentMap.clear();

      for (const tenant of response.data.data) {
        if (!tenant.config) continue;

        this.tenantMap.set(tenant.domain, tenant);

        if (!tenant.parent_id) {
          this.parentMap.set(tenant.id, tenant);
        }
      }

      console.log(`[TenantCacheService] Preloaded ${this.tenantMap.size} tenants`);
    } catch (e) {
      console.error('[TenantCacheService] Failed to preload tenants', e);
    }
  }

  /**
   * Normalizes the tenant configuration. The backend has a different structure for the config
   * than the frontend, so we need to transform it.
   */
  private normalizeConfig(tenant: Tenant): TenantConfigProtected {
    const cfg = tenant.config;

    return {
      ...cfg,
      tenantId: tenant.id,
      tenantName: tenant.name,
      __visibility: {
        ...cfg.__visibility,
        apiUrl: 'public',
        tenantId: 'public',
        tenantName: 'public',
      },
    };
  }

  /**
   * Retrieves the parent tenant of the given tenant.
   */
  private getParentTenant(tenant: Tenant): Tenant {
    if (tenant.parent_id) {
      const parent = this.parentMap.get(tenant.parent_id);

      if (parent) return parent;
    }

    return tenant;
  }
}
