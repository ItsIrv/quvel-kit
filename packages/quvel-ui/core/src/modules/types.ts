import type { RouteRecordRaw } from 'vue-router';
import { QuasarAnimations, QuasarPlugins } from 'quasar';
import type { ConfigureCallback } from '@quasar/app-vite';

// Extract the context type from ConfigureCallback
export type QuasarContext = Parameters<ConfigureCallback>[0];

/**
 * Service class type - generic to avoid hard dependencies
 */
export type ServiceClass<T = any> = new () => T;

/**
 * Build configuration for modules
 */
export interface ModuleBuildConfig {
  /** Boot files to include */
  boot?: Array<string | { path: string; server?: false; client?: false }>;

  /** CSS files to include */
  css?: string[];

  /** Animations to register */
  animations?: QuasarAnimations[];

  /** Quasar framework plugins */
  plugins?: (keyof QuasarPlugins)[];
}

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

  /**
   * Returns module build configuration
   */
  build?: (ctx?: QuasarContext) => ModuleBuildConfig;
}