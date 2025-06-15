import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { TenantConfig } from 'src/modules/Core/types/tenant.types';
import { SessionName } from 'src/modules/Auth/models/Session';

export function createConfig(ssrServiceOptions?: SsrServiceOptions): TenantConfig {
  // Try different config sources in order of preference
  let config: TenantConfig | null = null;

  // 1. SSR context (highest priority)
  if (ssrServiceOptions?.req?.tenantConfig) {
    config = ssrServiceOptions.req.tenantConfig;
  }

  // 2. Browser global from SSR hydration
  else if (typeof window !== 'undefined' && window.__TENANT_CONFIG__) {
    config = window.__TENANT_CONFIG__;
  }

  // 3. PWA cached config from localStorage
  else if (typeof window !== 'undefined') {
    config = getPWACachedConfig();

    // 4. NEW: Fetch from public config API (for non-SSR modes)
    if (!config && shouldFetchPublicConfig()) {
      // This will be handled by a boot file for async loading
      console.log('QuVel Kit: Public config fetch will be handled by boot process');
    }
  }

  // 5. Environment variables fallback
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
 * Check if we should attempt to fetch public config from API.
 */
function shouldFetchPublicConfig(): boolean {
  // Only fetch if we have a public config URL configured
  const publicConfigUrl = import.meta.env.VITE_PUBLIC_CONFIG_URL;
  return !!publicConfigUrl;
}

/**
 * Fetch tenant config from public API endpoint.
 */
export async function fetchPublicTenantConfig(): Promise<TenantConfig | null> {
  try {
    const publicConfigUrl = import.meta.env.VITE_PUBLIC_CONFIG_URL;
    const tenantDomain = import.meta.env.VITE_TENANT_DOMAIN || window.location.hostname;

    if (!publicConfigUrl) {
      console.warn('QuVel Kit: VITE_PUBLIC_CONFIG_URL not configured');
      return null;
    }

    console.log(`QuVel Kit: Fetching public config for domain: ${tenantDomain}`);

    const response = await fetch(`${publicConfigUrl}?domain=${encodeURIComponent(tenantDomain)}`, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      console.warn(
        `QuVel Kit: Public config fetch failed: ${response.status} ${response.statusText}`,
      );
      return null;
    }

    const data = await response.json();

    if (data.data) {
      console.log(`QuVel Kit: Successfully fetched public config for ${tenantDomain}`);
      return data.data as TenantConfig;
    }

    return null;
  } catch (error) {
    console.warn('QuVel Kit: Failed to fetch public tenant config:', error);
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
        console.log(
          `QuVel Kit: Using cached tenant config for ${domain}, ${JSON.stringify(parsed.config)}`,
        );
        return parsed.config;
      }
    }
  } catch (error) {
    console.warn('QuVel Kit: Failed to retrieve cached tenant config:', error);
  }

  return null;
}
