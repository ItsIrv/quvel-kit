import type { PiniaPluginContext } from 'pinia';
import type { QSsrContext } from '@quasar/app-vite';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { markRaw, ref } from 'vue';

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

  // Add devtools support for Quasar Dev + Vue Devtools
  if (process.env.NODE_ENV === 'development') {
    context.store._customProperties ??= new Set();
    context.store._customProperties.add('$container');
  }

  if (!Object.prototype.hasOwnProperty.call(context.store.$state, 'hasError')) {
    // hasError is defined within the plugin, so each store has their individual
    // state property
    // setting the variable on `$state`, allows it to be serialized during SSR
    context.store.$state.hasError = ref(false);
  }

  return {
    $container: rawContainer,
  };
}
