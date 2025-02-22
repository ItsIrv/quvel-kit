import type { ApiService } from 'src/services/ApiService';
import type { I18nService } from 'src/services/I18nService';
import type { Service } from 'src/services/Service';
import type { TaskService } from 'src/services/TaskService';
import type { ValidationService } from 'src/services/ValidationService';

/**
 * Defines the structure of the Dependency Injection (DI) container.
 */
export interface ServiceContainer {
  [key: string]: Service;
  api: ApiService;
  i18n: I18nService;
  validation: ValidationService;
  task: TaskService;
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
