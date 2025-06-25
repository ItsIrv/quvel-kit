import type { ModuleLoader } from './types/module.types';
import { getAllServices } from './config/services';
import { ServiceClass } from './types/service.types';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';

/**
 * Core Module Loader
 *
 * Core is the only module that should expose services via services().
 * All other modules use lazy loading - services are created when container.get(Service) is called.
 */
export const CoreModule: ModuleLoader = {
  /**
   * Returns Core module services - the only module that should have this
   */
  services: () => {
    const serviceMap = getAllServices();
    const services: Record<string, ServiceClass> = {};

    for (const [name, ServiceClass] of serviceMap.entries()) {
      services[name] = ServiceClass;
    }

    return services;
  },

  /**
   * Returns Core module translations
   */
  i18n: () => ({
    'en-US': enUSTranslations,
    'es-MX': esMXTranslations,
  }),

  /**
   * Returns Core module build configuration
   */
  build: (ctx) => {
    const isMultiTenant = process.env.SSR_MULTI_TENANT === 'true';
    const isSSRWithPWA = ctx?.modeName === 'ssr' && process.env.SSR_PWA === 'true';
    const needsTenantConfig = isMultiTenant && (ctx?.modeName !== 'ssr' || isSSRWithPWA);

    const bootFiles: Array<string | { path: string; server?: false; client?: false }> = [
      ...(needsTenantConfig ? ['../modules/Core/boot/app-config'] : []),
      '../modules/Core/boot/container',
    ];

    return {
      boot: bootFiles,
      plugins: ['Cookies', 'Notify', 'LocalStorage', 'Meta', 'Loading'],
      animations: ['fadeIn', 'fadeOut'],
    };
  },
};
