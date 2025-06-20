import { TenantConfigProtected } from '../types/tenant.types';
import { createConfigFromEnv as createConfigFromEnvPublic } from 'src/modules/Core/utils/configUtil';

/**
 * Creates a tenant config object from environment variables.
 * Used in single-tenant mode when SSR_MULTI_TENANT is false.
 */
export function createConfigFromEnv(): TenantConfigProtected {
  const config: TenantConfigProtected = {
    ...createConfigFromEnvPublic(),
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
