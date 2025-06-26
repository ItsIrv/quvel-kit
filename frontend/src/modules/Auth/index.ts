import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { RouteRecordRaw } from 'vue-router';
import { moduleResource } from '../moduleUtils';
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
  build: () => {
    const authGuardEnabled = process.env.VITE_AUTH_GUARD_ENABLED === 'true';
    const bootFiles: Array<string | { path: string; server?: false; client?: false }> = [
      {
        server: false,
        path: moduleResource('Auth', 'boot/pinia-hydrator'),
      },
    ];

    // Add auth guard boot file if enabled
    if (authGuardEnabled) {
      bootFiles.push(moduleResource('Auth', 'boot/auth-guard'));
    }

    return {
      boot: bootFiles,
    };
  },
};
