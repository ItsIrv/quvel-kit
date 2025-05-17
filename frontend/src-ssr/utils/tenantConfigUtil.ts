import { TenantConfigProtected, TenantConfigVisibilityRecords } from '../types/tenant.types';

/**
 * Creates a tenant config object from environment variables.
 * Used in single-tenant mode when VITE_MULTI_TENANT is false.
 */
export function createTenantConfigFromEnv(): TenantConfigProtected {
  return {
    apiUrl: process.env.VITE_API_URL || '',
    appUrl: process.env.VITE_APP_URL || '',
    appName: process.env.VITE_APP_NAME || '',
    internalApiUrl: process.env.VITE_INTERNAL_API_URL || '',
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
}

/**
 * Filters out non-public fields from the tenant config.
 */
export function filterTenantConfig(config: TenantConfigProtected): Partial<TenantConfigProtected> {
  const publicConfig: Partial<TenantConfigProtected> = {};

  Object.keys(config.__visibility).forEach((key) => {
    const typedKey = key as keyof TenantConfigVisibilityRecords;

    if (config.__visibility?.[typedKey] === 'public') {
      const value = config[typedKey];

      if (typeof value === 'string' || Array.isArray(value)) {
        publicConfig[typedKey] = value as string & string[];
      }
    }
  });

  return publicConfig;
}
