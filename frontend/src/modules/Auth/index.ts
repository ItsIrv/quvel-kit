import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { RouteRecordRaw } from 'vue-router';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';

/**
 * Auth Module Loader
 *
 * Provides simple APIs for other parts of the app to access Auth module resources.
 * Only loads resources when requested for performance.
 */
export const AuthModule: ModuleLoader = {
  /**
   * Returns Auth module routes
   */
  routes: (): RouteRecordRaw[] => {
    // Auth routes would go here when they exist
    return [];
  },

  /**
   * Returns Auth module translations
   */
  i18n: () => ({
    'en-US': enUSTranslations,
    'es-MX': esMXTranslations,
  }),

  /**
   * Returns Auth module build configuration
   */
  build: () => ({
    boot: [
      {
        server: false,
        path: '../modules/Auth/boot/pinia-hydrator',
      },
    ],
  }),
};
