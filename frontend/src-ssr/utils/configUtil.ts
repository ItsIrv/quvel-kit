import { AppConfigProtected, TenantConfigProtected } from '../types/tenant.types';
import { createConfigFromEnv as createConfigFromEnvPublic, createTenantConfigFromEnv as createTenantConfigFromEnvPublic } from 'src/modules/Core/utils/configUtil';

/**
 * Creates an app config object from environment variables.
 * Used in single-tenant mode when SSR_MULTI_TENANT is false.
 */
export function createAppConfigFromEnv(): AppConfigProtected {
  const config: AppConfigProtected = {
    ...createConfigFromEnvPublic(),
    __visibility: {
      apiUrl: 'public',
      appUrl: 'public',
      appName: 'public',
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
 * Creates a tenant config object from environment variables.
 * Used in single-tenant mode when SSR_MULTI_TENANT is false but tenant fields are needed.
 */
export function createTenantConfigFromEnv(): TenantConfigProtected {
  const config: TenantConfigProtected = {
    ...createTenantConfigFromEnvPublic(),
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
 * Legacy function for backward compatibility.
 * @deprecated Use createAppConfigFromEnv() or createTenantConfigFromEnv() instead.
 */
export function createConfigFromEnv(): AppConfigProtected {
  return createAppConfigFromEnv();
}

/**
 * Filters out non-public fields from any protected config.
 * Only returns fields marked as 'public' in the visibility settings.
 */
export function filterConfig(config: AppConfigProtected | TenantConfigProtected): Record<string, unknown> {
  const publicConfig: Record<string, unknown> = {};

  // First, build the filtered config with only public fields
  Object.entries(config.__visibility).forEach(([key, visibility]) => {
    if (visibility === 'public' && key in config) {
      const value = config[key as keyof typeof config];

      // Only include the value if it exists
      if (value !== undefined && value !== null) {
        publicConfig[key] = value;
      }
    }
  });

  return publicConfig;
}

/**
 * Legacy function for backward compatibility.
 * @deprecated Use filterConfig() instead.
 */
export function filterTenantConfig(config: TenantConfigProtected): Record<string, unknown> {
  return filterConfig(config);
}
