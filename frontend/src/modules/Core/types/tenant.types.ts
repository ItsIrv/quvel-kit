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

/** App assets configuration */
export interface AppAssets {
  css?: CSSAssetConfig[];
  js?: JSAssetConfig[];
}

/** App meta configuration */
export interface AppMeta {
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

/** Base application configuration interface */
export interface AppConfig {
  apiUrl: string;
  appUrl: string;
  appName: string;
  pusherAppKey: string;
  pusherAppCluster: string;
  socialiteProviders: string[];
  sessionCookie?: string;
  recaptchaGoogleSiteKey: string;
  assets?: AppAssets;
  meta?: AppMeta;
}

/** Extended tenant configuration with tenant-specific fields */
export interface TenantConfig extends AppConfig {
  tenantId: string;
  tenantName: string;
}

/** Legacy aliases for backward compatibility */
export type TenantAssets = AppAssets;
export type TenantMeta = AppMeta;
