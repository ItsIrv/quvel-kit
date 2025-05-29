import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRRegisterService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';

/**
 * SSR-specific configuration service
 * Provides configuration without browser dependencies
 */
export class SSRConfigService extends SSRService implements SSRRegisterService {
  private config: Record<string, unknown> = {};
  private logger!: SSRLogService;

  override register(container: SSRServiceContainer): void {
    this.logger = container.get(SSRLogService);

    // Log after registration when logger is available
    this.logger.debug('SSR Config loaded', {
      keys: Object.keys(this.config),
      environment: this.config.environment,
    });
  }

  override boot(): void {
    // Load config from environment
    this.config = {
      apiUrl: process.env.VITE_API_URL || '',
      appName: process.env.VITE_APP_NAME || 'Quvel',
      appUrl: process.env.VITE_APP_URL || '',
      environment: process.env.NODE_ENV || 'development',
      ssrKey: process.env.SSR_KEY || '',
    };

    // Don't log here since logger isn't registered yet
  }

  /**
   * Get a configuration value
   */
  get<T = unknown>(key: string, defaultValue?: T): T {
    const value = this.config[key];
    if (value === undefined) {
      this.logger.warning(`Config key not found: ${key}`, { key, defaultValue });
      return defaultValue as T;
    }
    return value as T;
  }

  /**
   * Get all configuration
   */
  all(): Record<string, unknown> {
    return { ...this.config };
  }

  /**
   * Set a configuration value (runtime only)
   */
  set(key: string, value: unknown): void {
    this.config[key] = value;
    this.logger.debug('Config value set', { key, value });
  }

  /**
   * Merge configuration
   */
  merge(config: Record<string, unknown>): void {
    this.config = { ...this.config, ...config };
    this.logger.debug('Config merged', { keys: Object.keys(config) });
  }
}