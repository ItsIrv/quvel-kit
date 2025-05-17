import { SessionName } from 'src/modules/Auth/models/Session';
import { TenantConfig } from 'src/modules/Core/types/tenant.types';

export class ConfigService {
  private readonly config: TenantConfig;

  constructor(ssrConfig?: TenantConfig) {
    // Check if running in browser and `window.__TENANT_CONFIG__` is available
    const clientConfig = ssrConfig
      ? null
      : ((window as unknown as { __TENANT_CONFIG__: TenantConfig }).__TENANT_CONFIG__ ?? null);

    // Prefer SSR config > Client Hydrated Config > Environment Variables
    this.config = ssrConfig ??
      clientConfig ?? {
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

  /**
   * Get a specific config value.
   * @param key - The config key.
   * @returns The config value.
   */
  public get<K extends keyof TenantConfig>(key: K): TenantConfig[K] {
    return this.config[key];
  }

  /**
   * Get all config values.
   */
  public getAll(): TenantConfig {
    return this.config;
  }
}
