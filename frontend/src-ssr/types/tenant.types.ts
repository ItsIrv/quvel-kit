/**
 * The tenant model.
 */
export interface Tenant {
  id: string;
  name: string;
  domain: string;
  parent_id: string | null;
  config: BackendConfig;
  created_at: string;
  updated_at: string;
}

/**
 * The tenant configuration (processed config used in app).
 */
export interface TenantConfigProtected {
  apiUrl: string;
  appUrl: string;
  appName: string;
  internalApiUrl?: string;
  tenantId: string;
  tenantName: string;
  pusherAppKey: string;
  pusherAppCluster: string;
  socialiteProviders: string[];
  __visibility: TenantConfigVisibilityRecord;
}

/**
 * The visibility of a tenant configuration field.
 */
export type TenantConfigVisibility = 'public' | 'protected';

export type TenantConfigVisibilityRecord = Partial<
  Record<Exclude<keyof TenantConfigProtected, '__visibility'>, TenantConfigVisibility>
>;

/**
 * The backend configuration (processed config used in app).
 */
export interface BackendConfig extends Omit<TenantConfigProtected, 'apiUrl'> {
  frontendUrl: string;
}

/**
 * The cached tenant configuration.
 */
export interface CachedTenantConfig {
  config: TenantConfigProtected;
  expiresAt: number;
}
