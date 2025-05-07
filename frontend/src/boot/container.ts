import { defineBoot } from '#q-app/wrappers';
import type { QSsrContext } from '@quasar/app-vite';
import { ContainerKey, setClientContainer } from 'src/modules/Core/composables/useContainer';
import { createApi } from 'src/modules/Core/utils/axiosUtil';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ValidationService } from 'src/modules/Core/services/ValidationService';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { I18nService } from 'src/modules/Core/services/I18nService';
import { createI18n } from 'src/modules/Core/utils/i18nUtil';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { WebSocketService } from 'src/modules/Core/services/WebSocketService';
import { createWebsocketConfig } from 'src/modules/Core/utils/websocketUtil';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { AuthService } from 'src/modules/Auth/services/AuthService';
import type { Service } from 'src/modules/Core/types/service.types';

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
    new Map<string, Service>([
      ['catalog', new CatalogService()],
      ['auth', new AuthService()],
    ]),
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
