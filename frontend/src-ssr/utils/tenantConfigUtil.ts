import { TenantConfigProtected } from '../types/tenant.types';

/**
 * Creates a tenant config object from environment variables.
 * Used in single-tenant mode when SSR_MULTI_TENANT is false.
 */
export function createTenantConfigFromEnv(): TenantConfigProtected {
  const config: TenantConfigProtected = {
    apiUrl: process.env.VITE_API_URL || '',
    appUrl: process.env.VITE_APP_URL || '',
    appName: process.env.VITE_APP_NAME || '',
    tenantId: process.env.VITE_TENANT_ID || '',
    tenantName: process.env.VITE_TENANT_NAME || '',
    pusherAppKey: process.env.VITE_PUSHER_APP_KEY || '',
    pusherAppCluster: process.env.VITE_PUSHER_APP_CLUSTER || '',
    socialiteProviders: (process.env.VITE_SOCIALITE_PROVIDERS || '').split(',').filter(Boolean),
    sessionCookie: process.env.VITE_SESSION_NAME || '',
    recaptchaGoogleSiteKey: process.env.VITE_RECAPTCHA_GOOGLE_SITE_KEY || '',
    __visibility: {
      apiUrl: 'public',
      appUrl: 'public',
      appName: 'public',
      tenantId: 'public',
      tenantName: 'public',
      pusherAppKey: 'public',
      pusherAppCluster: 'public',
      socialiteProviders: 'public',
      sessionCookie: 'protected',
      recaptchaGoogleSiteKey: 'public',
    },
  };

  if (process.env.VITE_INTERNAL_API_URL) {
    config.internalApiUrl = process.env.VITE_INTERNAL_API_URL;
  } else {
    config.internalApiUrl = config.apiUrl;
  }

  return config;
}

/**
 * Filters out non-public fields from the tenant config.
 * Only returns fields marked as 'public' in the visibility settings.
 */
export function filterTenantConfig(config: TenantConfigProtected): Record<string, unknown> {
  const publicConfig: Record<string, unknown> = {};

  // First, build the filtered config with only public fields
  Object.entries(config.__visibility).forEach(([key, visibility]) => {
    if (visibility === 'public' && key in config) {
      const value = config[key as keyof TenantConfigProtected];

      // Only include the value if it exists
      if (value !== undefined && value !== null) {
        publicConfig[key] = value;
      }
    }
  });

  return publicConfig;
}
