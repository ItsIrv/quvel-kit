import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import type { RouteRecordRaw } from 'vue-router';
import { moduleResource } from '../moduleUtils';
import routes from './router/routes';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';

/**
 * Dashboard Module Loader
 *
 * Provides a modern dashboard interface with responsive layout,
 * navigation sidebar, and member area functionality.
 */
export const DashboardModule: ModuleLoader = {
  /**
   * Returns Dashboard module routes
   */
  routes: (): RouteRecordRaw[] => {
    return routes;
  },

  /**
   * Returns Dashboard module translations
   */
  i18n: () => ({
    'en-US': enUSTranslations,
    'es-MX': esMXTranslations,
  }),

  /**
   * Returns Dashboard module build configuration
   */
  build: () => ({
    css: [moduleResource('Dashboard', 'css/dashboard.scss')],
  }),
};