import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { RouteRecordRaw } from 'vue-router';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';

/**
 * Notifications Module Loader
 * 
 * Provides simple APIs for other parts of the app to access Notifications module resources.
 * Only loads resources when requested for performance.
 */
export const NotificationsModule: ModuleLoader = {
  /**
   * Returns Notifications module routes
   */
  routes: (): RouteRecordRaw[] => {
    // Notifications routes would go here when they exist
    return [];
  },

  /**
   * Returns Notifications module translations
   */
  i18n: () => ({
    'en-US': enUSTranslations,
    'es-MX': esMXTranslations,
  }),

};