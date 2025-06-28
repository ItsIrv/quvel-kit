import type { ModuleLoader } from './types/module.types';
import { moduleResource } from '../moduleUtils';
import enUSTranslations from './i18n/en-US';
import esMXTranslations from './i18n/es-MX';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { LogService } from 'src/modules/Core/services/LogService';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { ValidationService } from 'src/modules/Core/services/ValidationService';
import { I18nService } from 'src/modules/Core/services/I18nService';

/**
 * Core Module Loader
 */
export const CoreModule: ModuleLoader = {
  /**
   * Returns services to be auto-loaded
   */
  services: () => {
    return {
      ConfigService,
      LogService,
      ApiService,
      TaskService,
      ValidationService,
      I18nService,
    };
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
      ...(needsTenantConfig ? [moduleResource('Core', 'boot/app-config')] : []),
      moduleResource('Core', 'boot/container'),
    ];

    return {
      boot: bootFiles,
      plugins: ['Cookies', 'Notify', 'LocalStorage', 'Meta', 'Loading'],
      animations: ['fadeIn', 'fadeOut'],
    };
  },
};
