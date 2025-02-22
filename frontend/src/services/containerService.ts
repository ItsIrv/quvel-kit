import type { QSsrContext } from '@quasar/app-vite';
import { type App, useSSRContext } from 'vue';
import type { ServiceContainer } from 'src/types/container.types';
import { createApiService } from 'src/utils/axiosUtil';
import { createI18nService } from 'src/utils/i18nUtil';
import { createValidationService } from 'src/utils/validationUtil';
import type { BootableService } from 'src/types/service.types';

/**
 * Symbol for the service container key.
 */
export const ContainerKey = Symbol('$container');

/**
 * Holds the singleton service container in the client.
 */
let clientContainer: ServiceContainer | null = null;

/**
 * Creates the service container per request.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The fully initialized service container.
 */
export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  const container: Partial<ServiceContainer> = {};

  // Create service instances
  container.api = createApiService(ssrContext);
  container.i18n = createI18nService(ssrContext);
  container.validation = createValidationService();

  // Boot all services
  for (const key in container) {
    const service = container[key as keyof ServiceContainer];

    if (typeof service === 'object' && 'boot' in service) {
      (service as BootableService).boot(container as ServiceContainer);
    }
  }

  // Register any optional logic
  for (const key in container) {
    const service = container[key as keyof ServiceContainer];

    if (typeof service === 'object' && 'register' in service) {
      (service as BootableService).register?.();
    }
  }

  return container as ServiceContainer;
}

/**
 * Assigns a singleton container in the browser.
 * @param app - The Vue App instance.
 * @param container - The service container.
 */
export function setClientContainer(app: App, container: ServiceContainer): void {
  if (typeof window !== 'undefined') {
    clientContainer = container;
  }
}

/**
 * Retrieves the service container.
 * Uses `ssrContext` in SSR, singleton in the client.
 * @returns The service container.
 */
export function useContainer(): ServiceContainer {
  if (typeof window === 'undefined') {
    const context = useSSRContext<QSsrContext>();

    if (context?.$container) {
      return context.$container;
    } else {
      throw new Error(
        'SSR container not initialized. Ensure ssrContext is set in container defineBoot.',
      );
    }
  } else {
    if (!clientContainer) {
      throw new Error(
        'Client container not initialized. Ensure `provideContainer()` was called in boot.',
      );
    }
    return clientContainer;
  }
}
