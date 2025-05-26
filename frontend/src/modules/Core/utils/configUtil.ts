import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { TenantConfig } from 'src/modules/Core/types/tenant.types';
import { SessionName } from 'src/modules/Auth/models/Session';

export function createConfig(ssrServiceOptions?: SsrServiceOptions): TenantConfig {
  // Check if running in browser and `window.__TENANT_CONFIG__` is available
  const config = ssrServiceOptions?.req?.tenantConfig ??
    (window as unknown as { __TENANT_CONFIG__: TenantConfig }).__TENANT_CONFIG__ ?? {
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

  return config;
}
