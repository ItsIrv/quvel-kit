import { QSsrContext } from '@quasar/app-vite';
import { type PiniaPluginContext } from 'pinia';
import { ServiceContainer } from 'src/types/container.types';
import { inject } from 'vue';

/**
 * Pinia Plugin to inject the entire DI container into all stores.
 */
export function piniaPlugin({ store }: PiniaPluginContext, ssrContext?: QSsrContext | null): void {
  // Use SSR context if available, otherwise fallback to injected container
  const container = ssrContext?.$container || (inject('$container') as ServiceContainer);

  // Attach the full container to all stores
  store.$container = container;
}
