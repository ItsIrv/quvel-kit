import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { TenantConfig } from 'src/modules/Core/types/tenant.types';
import { SessionName } from 'src/modules/Auth/models/Session';
import { Tenant } from 'app/src-ssr/types/tenant.types';

export function createConfig(ssrServiceOptions?: SsrServiceOptions): TenantConfig {
  // Try different config sources in order of preference
  let config: TenantConfig | null = null;

  if (ssrServiceOptions?.req?.tenantConfig) {
    config = ssrServiceOptions.req.tenantConfig;
  } else if (typeof window !== 'undefined' && window.__TENANT_CONFIG__) {
    config = window.__TENANT_CONFIG__;
  } else {
    config = createConfigFromEnv();
  }

  return config;
}

/**
 * Fetch tenant config from public API endpoint.
 */
export async function fetchPublicTenantConfig(): Promise<TenantConfig | null> {
  const cachedConfig = getPWACachedConfig();

  if (cachedConfig) {
    return cachedConfig;
  }

  try {
    const publicConfigEnabled = import.meta.env.VITE_PUBLIC_CONFIG_ENABLED === 'true';

    if (!publicConfigEnabled) {
      return null;
    }

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

    const data = (await response.json()) as { data: Tenant };

    if (data.data) {
      return data.data.config;
    }

    return null;
  } catch {
    return null;
  }
}

/**
 * Retrieve tenant config from PWA localStorage cache.
 */
function getPWACachedConfig(): TenantConfig | null {
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

export function createConfigFromEnv(): TenantConfig {
  const config: TenantConfig = {
    apiUrl: process.env.VITE_API_URL || '',
    appUrl: process.env.VITE_APP_URL || '',
    appName: process.env.VITE_APP_NAME || '',
    tenantId: process.env.VITE_TENANT_ID || '',
    tenantName: process.env.VITE_APP_NAME || '',
    pusherAppKey: process.env.VITE_PUSHER_APP_KEY || '',
    pusherAppCluster: process.env.VITE_PUSHER_APP_CLUSTER || '',
    socialiteProviders: (process.env.VITE_SOCIALITE_PROVIDERS || '').split(',').filter(Boolean),
    sessionCookie: process.env.VITE_SESSION_NAME || SessionName,
    recaptchaGoogleSiteKey: process.env.VITE_RECAPTCHA_GOOGLE_SITE_KEY || '',
  };

  return config;
}
