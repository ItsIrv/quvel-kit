import { defineStore } from '#q-app/wrappers';
import { createPinia } from 'pinia';
import { serviceContainerPlugin } from 'src/modules/Core/stores/plugins/serviceContainer';

/**
 * Pinia store factory.
 *
 * @param ssrContext - SSR context for Pinia.
 * @returns Pinia instance with service container plugin.
 */
export default defineStore(({ ssrContext }) => {
  const pinia = createPinia();

  pinia.use((context) => serviceContainerPlugin(context, ssrContext));

  return pinia;
});
