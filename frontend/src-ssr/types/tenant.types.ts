// Extend the Express Request object
declare module 'express' {
  interface Request {
    tenantConfig?: TenantConfigProtected;
  }
}

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
export interface TenantConfigProtected {
  api_url: string;
  app_url: string;
  app_name: string;
  internal_api_url?: string;
  __visibility?: Partial<Record<keyof TenantConfigProtected, TenantConfigVisibility>>;
}

/**
 * The visibility of a tenant configuration field.
 */
export type TenantConfigVisibility = 'public' | 'protected';
