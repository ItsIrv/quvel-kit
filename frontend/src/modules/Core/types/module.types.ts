import type { RouteRecordRaw } from 'vue-router';
import type { ServiceClass } from './service.types';

/**
 * Module Loader Interface
 * 
 * Defines the contract for module loaders to expose their resources.
 * All methods are optional - modules only implement what they need.
 */
export interface ModuleLoader {
  /**
   * Returns module services for lazy loading
   */
  services?: () => Record<string, ServiceClass>;

  /**
   * Returns module routes
   */
  routes?: () => RouteRecordRaw[];

  /**
   * Returns module translations by locale
   */
  i18n?: () => Record<string, Record<string, unknown>>;
}