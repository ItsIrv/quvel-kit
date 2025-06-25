import type { RouteRecordRaw } from 'vue-router';
import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { getAllModules } from 'src/config/modules';
import { ConfigureCallback } from '@quasar/app-vite';

// Extract the context type from ConfigureCallback
type QuasarContext = Parameters<ConfigureCallback>[0];

/**
 * Module Registry
 *
 * Provides access to configured modules and their resources.
 */
export const modules = getAllModules();

/**
 * Gets routes from all modules
 */
export function getModuleRoutes(): RouteRecordRaw[] {
  const routes: RouteRecordRaw[] = [];

  for (const [moduleName, moduleLoader] of Object.entries(modules)) {
    if (moduleLoader.routes) {
      try {
        const moduleRoutes = moduleLoader.routes();
        routes.push(...moduleRoutes);
      } catch (error) {
        console.warn(`Failed to load routes from ${moduleName} module:`, error);
      }
    }
  }

  return routes;
}

/**
 * Gets i18n translations from all modules for a specific locale
 */
export function getModuleI18n(locale: string): Record<string, unknown> {
  const translations: Record<string, unknown> = {};

  for (const [moduleName, moduleLoader] of Object.entries(modules)) {
    if (moduleLoader.i18n) {
      try {
        const moduleI18n = moduleLoader.i18n();
        const localeTranslations = moduleI18n[locale];

        if (localeTranslations) {
          Object.assign(translations, localeTranslations);
        }
      } catch (error) {
        console.warn(`Failed to load i18n from ${moduleName} module:`, error);
      }
    }
  }

  return translations;
}

/**
 * Gets services from all modules that need to be registered at boot time
 */
export function getModuleServices(): Record<string, ServiceClass> {
  const services: Record<string, ServiceClass> = {};

  for (const [moduleName, moduleLoader] of Object.entries(modules)) {
    if (moduleLoader.services) {
      try {
        const moduleServices = moduleLoader.services();
        Object.assign(services, moduleServices);
      } catch (error) {
        console.warn(`Failed to load services from ${moduleName} module:`, error);
      }
    }
  }

  return services;
}

/**
 * Gets merged build configuration from all modules
 */
export function getBuildConfig(ctx: QuasarContext): ReturnType<ConfigureCallback> {
  const mergedConfig: Partial<ReturnType<ConfigureCallback>> = {
    boot: [],
    animations: [],
    framework: {
      plugins: [],
    },
  };

  for (const [moduleName, moduleLoader] of Object.entries(modules)) {
    if (moduleLoader.build) {
      try {
        const buildConfig = moduleLoader.build(ctx);

        if (buildConfig.boot) {
          mergedConfig.boot?.push(...buildConfig.boot);
        }

        if (buildConfig.css) {
          if (!mergedConfig.css) {
            mergedConfig.css = [];
          }
          mergedConfig.css.push(...buildConfig.css);
        }

        if (buildConfig.animations && Array.isArray(mergedConfig.animations)) {
          mergedConfig.animations?.push(...buildConfig.animations);
        }

        if (buildConfig.plugins) {
          mergedConfig.framework?.plugins?.push(...buildConfig.plugins);
        }
      } catch (error) {
        console.warn(`Failed to load build config from ${moduleName} module:`, error);
      }
    }
  }

  return mergedConfig;
}
