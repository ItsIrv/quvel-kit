import type { PiniaPluginContext } from 'pinia';
import type { QSsrContext } from '@quasar/app-vite';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { markRaw } from 'vue';

/**
 * Pinia plugin to inject the DI container into every store as `$container`.
 * Handles SSR context or falls back to client container.
 */
export function serviceContainerPlugin(
  context: PiniaPluginContext,
  ssrContext?: QSsrContext | null,
) {
  // markRaw to prevent Vue from making it reactive
  const rawContainer = markRaw(ssrContext?.$container ?? useContainer());

  context.store.$container = rawContainer;

  return {
    $container: rawContainer,
  };
}
