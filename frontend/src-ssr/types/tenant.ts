// Extend the Express Request object
declare module 'express' {
  interface Request {
    tenantConfig?: TenantConfig;
  }
}

export interface Tenant {
  id: string;
  name: string;
  domain: string;
  parent_id: string | null;
  config: TenantConfig;
  created_at: string;
  updated_at: string;
}

export interface TenantConfig {
  apiUrl: string;
  appUrl: string;
  appName: string;
  appEnv: string;
  internalApiUrl?: string;
  debug: boolean;
}

export interface TenantConfigResponse {
  api_url: string;
  app_url: string;
  app_name: string;
  app_env: string;
  internal_api_url: string | null;
  debug: boolean;
}
