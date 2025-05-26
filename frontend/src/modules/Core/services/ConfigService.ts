import type { SsrAwareService, SsrServiceOptions } from 'src/modules/Core/types/service.types';
import { TenantConfig } from 'src/modules/Core/types/tenant.types';
import { createConfig } from '../utils/configUtil';
import { Service } from './Service';

export class ConfigService extends Service implements SsrAwareService {
  private config!: TenantConfig;

  /**
   * Boot method called with SSR context after service construction.
   */
  boot(ssrServiceOptions?: SsrServiceOptions): void {
    this.config = createConfig(ssrServiceOptions);
  }

  /**
   * Check if running in SSR context.
   */
  public isSSR(): boolean {
    return typeof window === 'undefined';
  }

  public get<K extends keyof TenantConfig>(key: K): TenantConfig[K] {
    return this.config?.[key] ?? null;
  }

  /**
   * Get all config values.
   */
  public getAll(): TenantConfig {
    return this.config;
  }
}
