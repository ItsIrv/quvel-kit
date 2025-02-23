import type { QSsrContext } from '@quasar/app-vite';
import { useSSRContext } from 'vue';
import type { ServiceContainer } from 'src/services/ServiceContainer';

/**
 * Symbol for the service container key.
 */
export const ContainerKey = Symbol('$container');

/**
 * Holds the singleton service container in the client.
 */
let clientContainer: ServiceContainer | null = null;

/**
 * Assigns a singleton container in the browser.
 * @param container - The service container.
 */
export function setClientContainer(container: ServiceContainer): void {
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
