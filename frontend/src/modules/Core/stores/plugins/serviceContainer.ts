import type { QSsrContext } from '@quasar/app-vite';
import { type PiniaPluginContext } from 'pinia';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Pinia Plugin to inject the entire DI container into all stores.
 */
export function serviceContainer(
  { store }: PiniaPluginContext,
  ssrContext?: QSsrContext | null,
): void {
  // Use SSR context if available, otherwise fallback to useContainer
  // Attach the full container to all stores
  store.$container = ssrContext?.$container || useContainer();
}
