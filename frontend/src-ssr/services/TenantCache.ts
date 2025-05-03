import { createAxios } from 'src/modules/Core/utils/axiosUtil';
import { Tenant, TenantConfigProtected } from '../types/tenant.types';

interface BackendConfig extends Omit<TenantConfigProtected, 'apiUrl'> {
  frontendUrl: string;
}

interface CachedTenantConfig {
  config: TenantConfigProtected;
  expiresAt: number;
}

export class TenantCacheService {
  private static instance: TenantCacheService;
  private readonly preloadMode: boolean;
  private readonly ttl: number;
  private readonly refreshInterval: number;

  private readonly domainCache = new Map<string, CachedTenantConfig>();
  private readonly tenantMap = new Map<string, Tenant & { config: BackendConfig }>();
  private readonly parentMap = new Map<string, Tenant & { config: BackendConfig }>();

  private constructor() {
    this.preloadMode = Boolean(process.env.VITE_SSR_PRELOAD_TENANTS);
    this.ttl = Number(process.env.VITE_SSR_TENANT_TTL) || 60 * 5;
    this.refreshInterval = Number(process.env.VITE_SSR_TENANT_REFRESH_INTERVAL) || this.ttl;

    setTimeout(
      () => setInterval(() => void this.loadAllTenants(), this.refreshInterval * 1000),
      this.refreshInterval * 1000,
    );
  }

  public static getInstance(): TenantCacheService {
    if (!this.instance) {
      this.instance = new TenantCacheService();
    }
    return this.instance;
  }

  public async getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null> {
    if (this.preloadMode) {
      const tenant = this.tenantMap.get(domain);
      if (!tenant?.config) return null;
      const parent = this.getParentTenant(tenant);
      return this.normalizeConfig(parent);
    }

    const now = Date.now();
    const cached = this.domainCache.get(domain);
    if (cached && cached.expiresAt > now) {
      return cached.config;
    }

    try {
      const response = await createAxios().get<{
        data: Tenant & { config: BackendConfig };
      }>(`${process.env.VITE_SSR_API_URL}/tenant`, { params: { domain } });

      const tenant = response.data.data;
      if (!tenant?.config) return null;

      const config = this.normalizeConfig(tenant);
      this.domainCache.set(domain, {
        config,
        expiresAt: now + this.ttl * 1000,
      });

      return config;
    } catch {
      console.error(`[TenantCacheService] Failed to fetch tenant [${domain}]`);
      return null;
    }
  }

  private normalizeConfig(tenant: Tenant & { config: BackendConfig }): TenantConfigProtected {
    const cfg = tenant.config;
    return {
      ...cfg,
      apiUrl: cfg.appUrl,
      appUrl: cfg.frontendUrl,
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

  public async loadAllTenants(): Promise<void> {
    try {
      const response = await createAxios().get<{
        data: (Tenant & { config: BackendConfig })[];
      }>(`${process.env.VITE_SSR_API_URL}/tenant/cache`);

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
    } catch {
      console.error('[TenantCacheService] Failed to preload tenants');
    }
  }

  private getParentTenant(tenant: Tenant): Tenant & { config: BackendConfig } {
    if (tenant.parent_id) {
      const parent = this.parentMap.get(tenant.parent_id);
      if (parent) return parent;
    }
    return tenant as Tenant & { config: BackendConfig };
  }
}
