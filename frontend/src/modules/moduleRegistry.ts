import type { RouteRecordRaw } from 'vue-router';
import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';
import { CoreModule } from 'src/modules/Core';

/**
 * Registered Modules
 * 
 * Add new modules here to make them available throughout the app.
 */
export const modules: Record<string, ModuleLoader> = {
  Core: CoreModule,
  Auth: AuthModule,
  Notifications: NotificationsModule,
  Quvel: QuvelModule,
};

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
