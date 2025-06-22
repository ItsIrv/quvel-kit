import { AppConfig, TenantConfig } from 'src/modules/Core/types/tenant.types';

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
 * Base app configuration with SSR-specific fields and visibility.
 * Used in single-tenant mode when SSR_MULTI_TENANT is false.
 */
export interface AppConfigProtected extends AppConfig {
  internalApiUrl?: string;
  __visibility: AppConfigVisibilityRecords;
}

/**
 * The tenant configuration (processed config used in app).
 * Used in multi-tenant mode when SSR_MULTI_TENANT is true.
 */
export interface TenantConfigProtected extends TenantConfig {
  internalApiUrl?: string;
  __visibility: TenantConfigVisibilityRecords;
}

/**
 * The visibility of configuration fields.
 */
export type ConfigVisibility = 'public' | 'protected';

/**
 * The visibility records for app configuration fields.
 */
export type AppConfigVisibilityRecords = Partial<
  Record<Exclude<keyof AppConfigProtected, '__visibility'>, ConfigVisibility>
>;

/**
 * The visibility records for tenant configuration fields.
 */
export type TenantConfigVisibilityRecords = Partial<
  Record<Exclude<keyof TenantConfigProtected, '__visibility'>, ConfigVisibility>
>;

/**
 * Union type for all protected config types.
 */
export type AnyConfigProtected = AppConfigProtected | TenantConfigProtected;

/**
 * Legacy alias for backward compatibility.
 */
export type TenantConfigVisibility = ConfigVisibility;

/**
 * The cached tenant configuration.
 */
export interface CachedTenantConfig {
  config: TenantConfigProtected;
  expiresAt: number;
}
