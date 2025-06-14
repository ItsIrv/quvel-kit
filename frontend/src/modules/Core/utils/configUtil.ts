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
  }

  if (!config) {
    config = {
      apiUrl: import.meta.env.VITE_API_URL ?? '',
      appUrl: import.meta.env.VITE_APP_URL ?? '',
      appName: import.meta.env.VITE_APP_NAME ?? 'QuVel',
      tenantId: import.meta.env.VITE_TENANT_ID ?? '',
      tenantName: import.meta.env.VITE_TENANT_NAME ?? '',
      pusherAppKey: import.meta.env.VITE_PUSHER_APP_KEY ?? '',
      pusherAppCluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? '',
      socialiteProviders: import.meta.env.VITE_SOCIALITE_PROVIDERS?.split(',') ?? [],
      sessionCookie: import.meta.env.VITE_SESSION_NAME ?? SessionName,
      recaptchaGoogleSiteKey: import.meta.env.VITE_RECAPTCHA_GOOGLE_SITE_KEY ?? '',
    };
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
    const publicConfigUrl = import.meta.env.VITE_PUBLIC_CONFIG_URL;
    const tenantDomain = import.meta.env.VITE_TENANT_DOMAIN || window.location.hostname;

    if (!publicConfigUrl) {
      return null;
    }

    const response = await fetch(`${publicConfigUrl}?domain=${encodeURIComponent(tenantDomain)}`, {
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
