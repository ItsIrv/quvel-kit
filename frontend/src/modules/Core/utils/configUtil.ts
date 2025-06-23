import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { AppConfig, TenantConfig } from 'src/modules/Core/types/tenant.types';
import { SessionName } from 'src/modules/Auth/models/Session';
// Removed SSR import to avoid circular dependencies

export function createConfig<T extends AppConfig = AppConfig>(
  ssrServiceOptions?: SsrServiceOptions,
): T {
  // Try different config sources in order of preference
  let config: T | null = null;

  if (ssrServiceOptions?.req?.ssrContext?.appConfig) {
    config = ssrServiceOptions.req.ssrContext.appConfig as unknown as T;
  } else if (typeof window !== 'undefined' && window.__APP_CONFIG__) {
    config = window.__APP_CONFIG__ as T;
  } else {
    config = createConfigFromEnv() as T;
  }

  return config;
}

/**
 * Fetch app config from public API endpoint.
 */
export async function fetchPublicAppConfig(): Promise<AppConfig | null> {
  const publicConfigEnabled = import.meta.env.VITE_PUBLIC_CONFIG_ENABLED === 'true';

  if (!publicConfigEnabled) {
    return null;
  }

  const cachedConfig = getPWACachedConfig();

  if (cachedConfig) {
    return cachedConfig;
  }

  try {
    // Construct URL from current host - no cross-domain requests allowed
    const protocol = window.location.protocol;
    const hostname = window.location.hostname;
    const port = window.location.port ? `:${window.location.port}` : '';
    const publicConfigUrl = `${protocol}//${hostname}${port}/api/tenant-info/public`;

    const response = await fetch(publicConfigUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      return null;
    }

    const data = (await response.json()) as { data: { config: AppConfig } };

    if (data.data) {
      return data.data.config;
    }

    return null;
  } catch {
    return null;
  }
}

/**
 * Retrieve app config from PWA localStorage cache.
 */
function getPWACachedConfig(): AppConfig | null {
  try {
    const domain = window.location.hostname;
    const configKey = `quvel_tenant_config_${domain}`;
    const cachedData = localStorage.getItem(configKey);

    if (cachedData) {
      const parsed = JSON.parse(cachedData);

      // Validate cached data structure
      if (parsed.config && parsed.domain === domain) {
        return parsed.config;
      }
    }
  } catch {
    // Silent fail for cached config
  }

  return null;
}

export function createConfigFromEnv(): AppConfig {
  const config: AppConfig = {
    apiUrl: process.env.VITE_API_URL || '',
    appUrl: process.env.VITE_APP_URL || '',
    appName: process.env.VITE_APP_NAME || '',
    pusherAppKey: process.env.VITE_PUSHER_APP_KEY || '',
    pusherAppCluster: process.env.VITE_PUSHER_APP_CLUSTER || '',
    socialiteProviders: (process.env.VITE_SOCIALITE_PROVIDERS || '').split(',').filter(Boolean),
    sessionCookie: process.env.VITE_SESSION_NAME || SessionName,
    recaptchaGoogleSiteKey: process.env.VITE_RECAPTCHA_GOOGLE_SITE_KEY || '',
  };

  return config;
}

export function createTenantConfigFromEnv(): TenantConfig {
  const baseConfig = createConfigFromEnv();
  const tenantConfig: TenantConfig = {
    ...baseConfig,
    tenantId: process.env.VITE_TENANT_ID || '',
    tenantName: process.env.VITE_TENANT_NAME || process.env.VITE_APP_NAME || '',
  };

  return tenantConfig;
}
