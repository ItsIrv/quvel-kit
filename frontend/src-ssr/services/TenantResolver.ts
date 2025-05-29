import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRRegisterService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import { SSRTenantCacheService } from './SSRTenantCacheService';
import { TenantConfigProtected } from '../types/tenant.types';

/**
 * Simple tenant resolver service that delegates to cache
 */
export class TenantResolver extends SSRService implements SSRRegisterService {
  private logger!: SSRLogService;
  private cache!: SSRTenantCacheService;

  override register(container: SSRServiceContainer): void {
    this.logger = container.get(SSRLogService);
    this.cache = container.get(SSRTenantCacheService);
  }

  /**
   * Retrieves the tenant configuration by domain.
   * Delegates to the cache service which handles preloading and caching.
   */
  async getTenantConfigByDomain(domain: string): Promise<TenantConfigProtected | null> {
    this.logger.debug('Resolving tenant config', { domain });
    
    const config = await this.cache.getTenantConfigByDomain(domain);
    
    if (config) {
      this.logger.debug('Tenant config resolved', { 
        domain, 
        tenantId: config.tenantId,
        tenantName: config.tenantName,
      });
    } else {
      this.logger.warning('No tenant config found', { domain });
    }
    
    return config;
  }
}