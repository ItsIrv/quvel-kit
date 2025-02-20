import { defineStore } from '#q-app/wrappers';
import { createPinia } from 'pinia';
import { piniaPlugin } from './piniaPlugin';
import { ServiceContainer } from 'src/types/container.types';

/*
 * When adding new properties to stores, you should also
 * extend the `PiniaCustomProperties` interface.
 * @see https://pinia.vuejs.org/core-concepts/plugins.html#typing-new-store-properties
 */
declare module 'pinia' {
  export interface PiniaCustomProperties {
    $container: ServiceContainer;
    // add your custom properties here, if any
  }
}

/*
 * If not building with SSR mode, you can
 * directly export the Store instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Store instance.
 */

export default defineStore(({ ssrContext }) => {
  const pinia = createPinia();

  // You can add Pinia plugins here
  pinia.use((context) => piniaPlugin(context, ssrContext));

  return pinia;
});
