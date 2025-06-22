import type { SsrAwareService, SsrServiceOptions } from 'src/modules/Core/types/service.types';
import { AppConfig, TenantConfig } from 'src/modules/Core/types/tenant.types';
import { createConfig } from '../utils/configUtil';
import { Service } from './Service';

export class ConfigService<T extends AppConfig = AppConfig> extends Service implements SsrAwareService {
  private config!: T;

  /**
   * Boot method called with SSR context after service construction.
   */
  boot(ssrServiceOptions?: SsrServiceOptions): void {
    this.config = createConfig<T>(ssrServiceOptions);
  }

  /**
   * Check if running in SSR context.
   */
  public isSSR(): boolean {
    return typeof window === 'undefined';
  }

  public get<K extends keyof T>(key: K): T[K] | null {
    return this.config?.[key] ?? null;
  }

  /**
   * Get all config values.
   */
  public getAll(): T {
    return this.config;
  }

  /**
   * Get tenant ID (only available if config has tenant fields)
   */
  public getTenantId(): string | null {
    if (this.isTenantConfig()) {
      const config = this.config as unknown as TenantConfig;
      return config.tenantId ?? null;
    }
    return null;
  }

  /**
   * Get tenant name (only available if config has tenant fields)
   */
  public getTenantName(): string | null {
    if (this.isTenantConfig()) {
      const config = this.config as unknown as TenantConfig;
      return config.tenantName ?? null;
    }
    return null;
  }

  /**
   * Check if this is a tenant configuration
   */
  public isTenantConfig(): boolean {
    const config = this.config as unknown as Record<string, unknown>;
    return 'tenantId' in config && 'tenantName' in config && 
           config.tenantId !== undefined && config.tenantId !== null &&
           config.tenantName !== undefined && config.tenantName !== null;
  }
}
