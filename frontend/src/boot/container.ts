import { defineBoot } from '#q-app/wrappers';
import { createApi } from 'src/utils/axiosUtil';
import type { QSsrContext } from '@quasar/app-vite';
import type { ServiceContainer } from 'src/types/container.types';
import { provideContainer } from 'src/services/containerService';

/**
 * Creates the service container per request.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The service container.
 */
function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  return {
    api: createApi(ssrContext),
  };
}

/**
 * Boot function to provide services globally.
 */
export default defineBoot(({ ssrContext, app }) => {
  const container = createContainer(ssrContext);

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    provideContainer(app, container);
  }
});
