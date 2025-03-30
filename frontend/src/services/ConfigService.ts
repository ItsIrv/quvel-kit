import { TenantConfig } from 'src/types/tenant.types';

declare global {
  interface Window {
    __TENANT_CONFIG__?: TenantConfig;
  }
}

export class ConfigService {
  private readonly config: TenantConfig;

  constructor(ssrConfig?: TenantConfig) {
    // Check if running in browser and `window.__TENANT_CONFIG__` is available
    const clientConfig =
      typeof window !== 'undefined' && window.__TENANT_CONFIG__ ? window.__TENANT_CONFIG__ : null;

    // Prefer SSR config > Client Hydrated Config > Environment Variables
    this.config = ssrConfig ??
      clientConfig ?? {
        api_url: import.meta.env.VITE_API_URL ?? '',
        app_url: import.meta.env.VITE_APP_URL ?? '',
        app_name: import.meta.env.VITE_APP_NAME ?? 'QuVel',
      };
  }

  /**
   * Get a specific config value.
   * @param key - The config key.
   * @returns The config value.
   */
  public get<T = string>(key: keyof typeof this.config): T {
    return this.config[key] as T;
  }

  /**
   * Get all config values.
   */
  public getAll(): TenantConfig {
    return this.config;
  }
}
