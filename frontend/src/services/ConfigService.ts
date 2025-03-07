import { AppConfig } from 'src/types/config.types';

declare global {
  interface Window {
    __TENANT_CONFIG__?: AppConfig;
  }
}

export class ConfigService {
  private config: AppConfig;

  constructor(ssrConfig?: AppConfig) {
    // Check if running in browser and `window.__TENANT_CONFIG__` is available
    const clientConfig =
      typeof window !== 'undefined' && window.__TENANT_CONFIG__ ? window.__TENANT_CONFIG__ : null;

    // Prefer SSR config > Client Hydrated Config > Environment Variables
    this.config = ssrConfig ??
      clientConfig ?? {
        apiUrl: import.meta.env.VITE_API_URL ?? '',
        internalApiUrl: import.meta.env.VITE_API_INTERNAL_URL ?? '',
        appUrl: import.meta.env.VITE_APP_URL ?? '',
        appName: import.meta.env.VITE_APP_NAME ?? 'QuVel',
        appEnv: import.meta.env.VITE_APP_ENV ?? 'production',
        debug: import.meta.env.VITE_DEBUG === 'true',
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
  public getAll(): AppConfig {
    return this.config;
  }
}
