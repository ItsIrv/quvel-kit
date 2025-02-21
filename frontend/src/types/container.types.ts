import type { AxiosInstance } from 'axios';
// import type { I18n } from 'vue-i18n';

/**
 * Defines the structure of the Dependency Injection (DI) container.
 */
export interface ServiceContainer {
  api: AxiosInstance;
  // Future services can be added here:
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
