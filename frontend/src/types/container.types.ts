import type { AxiosInstance } from 'axios';
import type { I18nType } from 'src/types/i18n.types';

/**
 * Defines the structure of the Dependency Injection (DI) container.
 */
export interface ServiceContainer {
  api: AxiosInstance;
  i18n: I18nType;
}

/**
 * Declares the Service Container for SSR Context.
 */
declare module '@quasar/app-vite' {
  interface QSsrContext {
    $container: ServiceContainer;
  }
}

declare module 'pinia' {
  export interface PiniaCustomProperties {
    $container: ServiceContainer;
  }
}
