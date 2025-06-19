import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRSingletonService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import { SSRApiService } from './SSRApiService';
import { SSREndpointConfigService } from './SSREndpointConfigService';
import { CachedTenantConfig, Tenant, TenantConfigProtected } from '../types/tenant.types';

/**
 * SSR Tenant Cache Service
 * Manages tenant configuration caching with preload support
 */
export class SSRTenantCacheService extends SSRService implements SSRSingletonService {
  private logger!: SSRLogService;
  private api!: SSRApiService;
  private endpointConfig!: SSREndpointConfigService;

  // Cache configuration
  private readonly preloadMode: boolean;
  private readonly resolverTtl: number;
  private readonly cacheTtl: number;
  private readonly enableCache: boolean;

  // Cache storage
  private readonly domainCache = new Map<string, CachedTenantConfig>();
  private readonly tenantMap = new Map<string, Tenant>();
  private readonly parentMap = new Map<string, Tenant>();

  // Cache refresh interval
  private intervalId: NodeJS.Timeout | null = null;

  // Track preload status
  private preloadCompleted = false;

  constructor() {
    super();

    // Load configuration from environment
    this.preloadMode = process.env.SSR_TENANT_SSR_PRELOAD_TENANTS === 'true';
    this.resolverTtl = Number(process.env.SSR_TENANT_SSR_RESOLVER_TTL) || 60 * 5; // 5 minutes
    this.cacheTtl = Number(process.env.SSR_TENANT_SSR_CACHE_TTL) || 60 * 5; // 5 minutes
    this.enableCache = process.env.SSR_ENABLE_CACHE === 'true';
  }

  override register(container: SSRServiceContainer): void {
    this.logger = container.get(SSRLogService);
    this.api = container.get(SSRApiService);
    this.endpointConfig = container.get(SSREndpointConfigService);

    // Initialize cache if enabled (don't await to avoid blocking service registration)
    if (this.shouldUseCache()) {
      void this.initializeCache();
    }
  }

  /**
   * Initialize cache and preload if configured
   */
  private async initializeCache(): Promise<void> {
    if (this.shouldUseCache()) {
      this.logger.info('Initializing tenant cache', {
        preloadMode: this.preloadMode,
        resolverTtl: this.resolverTtl,
        cacheTtl: this.cacheTtl,
      });

      // Preload all tenants if enabled
      if (this.preloadMode) {
        await this.loadAllTenants();

        // Set up refresh interval
        this.intervalId = setInterval(() => {
          void this.loadAllTenants();
        }, this.cacheTtl * 1000);
      }
    }
  }

  /**
   * Check if cache should be used
   */
  private shouldUseCache(): boolean {
    return this.enableCache;
  }

  /**
   * Get tenant configuration by domain
   */
  async getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null> {
    try {
      // If preload mode is enabled and completed, use the preloaded tenant map
      if (this.shouldUseCache() && this.preloadMode && this.preloadCompleted) {
        const tenant = this.tenantMap.get(domain);

        if (!tenant?.config) {
          this.logger.warning('Tenant not found in preloaded cache', {
            domain,
            availableDomains: Array.from(this.tenantMap.keys()).slice(0, 5),
          });
          return null;
        }

        const parent = this.getParentTenant(tenant);
        return this.normalizeConfig(parent);
      }

      // If preload mode is enabled but not completed, fall back to direct API call
      if (this.shouldUseCache() && this.preloadMode && !this.preloadCompleted) {
        this.logger.debug('Preload not completed, falling back to API call', { domain });
      }

      // Otherwise, check domain cache with TTL
      const now = Date.now();
      const cached = this.domainCache.get(domain);

      if (cached && cached.expiresAt > now && this.shouldUseCache()) {
        this.logger.debug('Tenant config found in cache', { domain });
        return cached.config;
      }

      // Fetch from API
      this.logger.debug('Fetching tenant config from API', { domain });

      const tenantEndpoint = this.endpointConfig.getTenantProtectedEndpoint();
      this.logger.debug('Using tenant endpoint', { endpoint: tenantEndpoint });

      const response = await this.api.get<{ data: Tenant }>(tenantEndpoint, {
        headers: {
          'X-Tenant-Domain': domain,
        },
      });

      const tenant = response.data;

      if (!tenant?.config) {
        this.logger.warning('Tenant has no config', { domain, tenantId: tenant?.id });
        return null;
      }

      const config = this.normalizeConfig(tenant);

      // Cache the result
      if (this.shouldUseCache()) {
        this.domainCache.set(domain, {
          config,
          expiresAt: now + this.resolverTtl * 1000,
        });

        this.logger.debug('Tenant config cached', {
          domain,
          expiresAt: new Date(now + this.resolverTtl * 1000).toISOString(),
        });
      }

      return config;
    } catch (error) {
      this.logger.error('Failed to fetch tenant config', {
        domain,
        error: error instanceof Error ? error.message : 'Unknown error',
      });
      return null;
    }
  }

  /**
   * Load all tenants for preload mode
   */
  private async loadAllTenants(): Promise<void> {
    try {
      const tenantCacheEndpoint = this.endpointConfig.getTenantCacheEndpoint();
      this.logger.info('Loading all tenants for cache', { endpoint: tenantCacheEndpoint });

      const response = await this.api.get<{ data: Tenant[] }>(tenantCacheEndpoint);

      this.tenantMap.clear();
      this.parentMap.clear();

      const domains: string[] = [];
      for (const tenant of response.data) {
        if (!tenant.config) continue;

        this.tenantMap.set(tenant.domain, tenant);
        domains.push(tenant.domain);

        if (!tenant.parent_id) {
          this.parentMap.set(tenant.id, tenant);
        }
      }

      this.logger.info('Tenants preloaded', {
        count: this.tenantMap.size,
        parents: this.parentMap.size,
        domains: domains.slice(0, 10), // Show first 10 domains for debugging
      });

      // Mark preload as completed
      this.preloadCompleted = true;
    } catch (error) {
      this.logger.error('Failed to preload tenants', {
        error: error instanceof Error ? error.message : 'Unknown error',
      });
    }
  }

  /**
   * Normalize tenant configuration for frontend
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
   * Get parent tenant for inheritance
   */
  private getParentTenant(tenant: Tenant): Tenant {
    if (tenant.parent_id) {
      const parent = this.parentMap.get(tenant.parent_id);
      if (parent) {
        this.logger.debug('Using parent tenant config', {
          tenantId: tenant.id,
          parentId: tenant.parent_id,
        });
        return parent;
      }
    }

    return tenant;
  }

  /**
   * Clear cache (useful for testing or manual refresh)
   */
  clearCache(): void {
    this.domainCache.clear();
    this.tenantMap.clear();
    this.parentMap.clear();
    this.logger.info('Tenant cache cleared');
  }

  /**
   * Cleanup on service shutdown
   */
  override destroy(): void {
    if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
      this.logger.info('Tenant cache refresh interval cleared');
    }
  }
}
