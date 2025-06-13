import { SSRServiceContainer } from '../services/SSRServiceContainer';
import type { SSRServiceClassGeneric } from '../types/service.types';
import { SSRLogService } from '../services/SSRLogService';
import { SSRApiService } from '../services/SSRApiService';
import { SSRTenantCacheService } from '../services/SSRTenantCacheService';
import { TenantResolver } from '../services/TenantResolver';
import { SSRRequestHandler } from '../services/SSRRequestHandler';

/**
 * Get SSR-specific services
 */
function getSSRServices(): Map<string, SSRServiceClassGeneric> {
  return new Map<string, SSRServiceClassGeneric>([
    // Core SSR services
    ['SSRLogService', SSRLogService],
    ['SSRApiService', SSRApiService],
    ['SSRTenantCacheService', SSRTenantCacheService],
    // SSR-specific services
    ['TenantResolver', TenantResolver],
    ['SSRRequestHandler', SSRRequestHandler],
  ]);
}

/**
 * Create SSR container with SSR-specific services only
 */
export function createSSRContainer(): SSRServiceContainer {
  return new SSRServiceContainer(getSSRServices());
}
