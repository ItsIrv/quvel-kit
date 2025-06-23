import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { RouteRecordRaw } from 'vue-router';
import routes from './router/routes';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';

/**
 * Quvel Module Loader
 * 
 * Provides simple APIs for other parts of the app to access Quvel module resources.
 * Only loads resources when requested for performance.
 */
export const QuvelModule: ModuleLoader = {
  /**
   * Returns Quvel module routes
   */
  routes: (): RouteRecordRaw[] => {
    return routes;
  },

  /**
   * Returns Quvel module translations
   */
  i18n: () => ({
    'en-US': enUSTranslations,
    'es-MX': esMXTranslations,
  }),
};