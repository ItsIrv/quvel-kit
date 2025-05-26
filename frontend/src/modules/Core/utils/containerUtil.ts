import type { QSsrContext } from '@quasar/app-vite';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { I18nService } from 'src/modules/Core/services/I18nService';
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import { LogService } from 'src/modules/Core/services/LogService';
import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { ServiceClass } from 'src/modules/Core/types/service.types';

/**
 * Creates the service container per request.
 *
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The fully initialized service container.
 *
 * TODO: In SSR context, should we have reusable singletons?
 */
export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  // Create SSR service context if we have req/res
  const serviceContext: SsrServiceOptions | undefined = ssrContext
    ? {
        req: ssrContext.req,
        res: ssrContext.res,
      }
    : undefined;

  // Create service container with service classes
  // The container will automatically:
  // 1. Instantiate each service
  // 2. Check if service has a boot method
  // 3. Pass SSR context only to services that need it
  // 4. Call register() on services that implement RegisterService
  const serviceClasses = new Map<string, ServiceClass>([
    ['ConfigService', ConfigService],
    ['LogService', LogService],
    ['ApiService', ApiService],
    ['I18nService', I18nService],
    // ['ValidationService', ValidationService],
    // ['TaskService', TaskService],
    // ['WebSocketService', WebSocketService],
  ]);

  return new ServiceContainer(serviceContext, serviceClasses);
}
