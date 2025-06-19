/** Asset configuration for CSS */
export interface CSSAssetConfig {
  url?: string;
  inline?: string;
  media?: string;
  integrity?: string;
  crossorigin?: string;
  position?: 'head' | 'body-start' | 'body-end'; // Where to inject
  priority?: 'critical' | 'normal' | 'low'; // Loading priority
}

/** Asset configuration for JavaScript */
export interface JSAssetConfig {
  url?: string;
  inline?: string;
  defer?: boolean;
  async?: boolean;
  integrity?: string;
  crossorigin?: string;
  position?: 'head' | 'body-start' | 'body-end'; // Where to inject
  priority?: 'critical' | 'normal' | 'low'; // Loading priority
  loading?: 'immediate' | 'deferred' | 'lazy'; // When to load
}

/** Tenant assets configuration */
export interface TenantAssets {
  css?: CSSAssetConfig[];
  js?: JSAssetConfig[];
}

/** Tenant meta configuration */
export interface TenantMeta {
  title?: string;
  titleTemplate?: string;
  description?: string;
  keywords?: string;
  ogTitle?: string;
  ogDescription?: string;
  ogImage?: string;
  twitterTitle?: string;
  twitterDescription?: string;
  twitterImage?: string;
}

/** Config type for SPA */
export interface TenantConfig {
  apiUrl: string;
  appUrl: string;
  appName: string;
  tenantId: string;
  tenantName: string;
  pusherAppKey: string;
  pusherAppCluster: string;
  socialiteProviders: string[];
  sessionCookie?: string;
  recaptchaGoogleSiteKey: string;
  assets?: TenantAssets;
  meta?: TenantMeta;
}
