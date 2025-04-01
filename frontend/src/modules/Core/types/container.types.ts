import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

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
