import { defineBoot } from '#q-app/wrappers';
import { ContainerKey, setClientContainer } from 'src/composables/useContainer';
import type { QSsrContext } from '@quasar/app-vite';
import { createApiService } from 'src/utils/axiosUtil';
import { createI18nService } from 'src/utils/i18nUtil';
import { createValidationService } from 'src/utils/validationUtil';
import { TaskService } from 'src/services/TaskService';
import { ServiceContainer } from 'src/services/ServiceContainer';

/**
 * Creates the service container per request.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The fully initialized service container.
 */
export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  return new ServiceContainer(
    createApiService(ssrContext),
    createI18nService(ssrContext),
    createValidationService(),
    new TaskService(),
    new Map(),
  );
}

/**
 * Boot function to provide the container service.
 */
export default defineBoot(({ ssrContext, app }) => {
  const container = createContainer(ssrContext);

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    app.provide(ContainerKey, container);

    setClientContainer(container);
  }

  app.use(container.i18n.instance);
});
