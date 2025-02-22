import type { QSsrContext } from '@quasar/app-vite';
import { type PiniaPluginContext } from 'pinia';
import { useContainer } from 'src/services/ContainerService';

/**
 * Pinia Plugin to inject the entire DI container into all stores.
 */
export function piniaPlugin({ store }: PiniaPluginContext, ssrContext?: QSsrContext | null): void {
  // Use SSR context if available, otherwise fallback to useContainer
  const container = ssrContext?.$container || useContainer();

  // Attach the full container to all stores
  store.$container = container;
}
