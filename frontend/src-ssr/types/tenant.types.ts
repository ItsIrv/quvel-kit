import { TenantConfig } from 'src/modules/Core/types/tenant.types';

/**
 * The tenant model.
 */
export interface Tenant {
  id: string;
  name: string;
  domain: string;
  parent_id: string | null;
  config: TenantConfigProtected;
  created_at: string;
  updated_at: string;
}

/**
 * The tenant configuration (processed config used in app).
 */
export interface TenantConfigProtected extends TenantConfig {
  internalApiUrl?: string;
  __visibility: TenantConfigVisibilityRecords;
}

/**
 * The visibility of a tenant configuration fields.
 */
export type TenantConfigVisibility = 'public' | 'protected';

/**
 * The visibility of a tenant configuration fields.
 */
export type TenantConfigVisibilityRecords = Partial<
  Record<Exclude<keyof TenantConfigProtected, '__visibility'>, TenantConfigVisibility>
>;

/**
 * The cached tenant configuration.
 */
export interface CachedTenantConfig {
  config: TenantConfigProtected;
  expiresAt: number;
}
