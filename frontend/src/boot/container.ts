import { defineBoot } from '#q-app/wrappers';
import type { QSsrContext } from '@quasar/app-vite';
import { ContainerKey, setClientContainer } from 'src/composables/useContainer';
import { createApi } from 'src/utils/axiosUtil';
import { TaskService } from 'src/services/TaskService';
import { ServiceContainer } from 'src/services/ServiceContainer';
import { ValidationService } from 'src/services/ValidationService';
import { ApiService } from 'src/services/ApiService';
import { I18nService } from 'src/services/I18nService';
import { createI18n } from 'src/utils/i18nUtil';
import { ConfigService } from 'src/services/ConfigService';
import { WebSocketService } from 'src/services/WebSocketService';
import { createWebsocketConfig } from 'src/utils/websocketUtil';

/**
 * Creates the service container per request.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The fully initialized service container.
 */
export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  const configService = new ConfigService(ssrContext?.req?.tenantConfig);
  const configOverrides = configService.getAll();

  return new ServiceContainer(
    configService,
    new ApiService(createApi(ssrContext, configOverrides)),
    new I18nService(createI18n(ssrContext)),
    new ValidationService(),
    new TaskService(),
    new WebSocketService(createWebsocketConfig(configOverrides)),
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
