import type { QSsrContext } from '@quasar/app-vite';
import { createApi } from 'src/modules/Core/utils/axiosUtil';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ValidationService } from 'src/modules/Core/services/ValidationService';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { I18nService } from 'src/modules/Core/services/I18nService';
import { createI18n } from 'src/modules/Core/utils/i18nUtil';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { WebSocketService } from 'src/modules/Core/services/WebSocketService';
import { LogService } from 'src/modules/Core/services/LogService';
import { createWebsocketConfig } from 'src/modules/Core/utils/websocketUtil';

/**
 * Creates the service container per request.
 *
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The fully initialized service container.
 *
 * TODO: In SSR context, should be have reusable singletons?
 */
export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  const configService = new ConfigService(ssrContext?.req?.tenantConfig);
  const configOverrides = configService.getAll();

  const logService = new LogService(
    ssrContext?.req?.__TRACE__ ?? {
      id: '',
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV,
      tenant: ssrContext?.req?.tenantConfig?.tenantId ?? 'unknown',
      runtime: 'server',
    },
  );

  return new ServiceContainer(
    configService,
    logService,
    new ApiService(createApi(ssrContext, configOverrides)),
    new I18nService(createI18n(ssrContext)),
    new ValidationService(),
    new TaskService(),
    new WebSocketService(createWebsocketConfig(configOverrides)),
  );
}
