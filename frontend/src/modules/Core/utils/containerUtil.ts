import type { QSsrContext } from '@quasar/app-vite';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
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
export function createContainer(
  ssrContext?: QSsrContext | null,
  serviceClasses?: Map<string, ServiceClass>,
): ServiceContainer {
  // Create SSR service context if we have req/res
  const serviceContext: SsrServiceOptions | undefined = ssrContext
    ? {
        req: ssrContext.req,
        res: ssrContext.res,
      }
    : undefined;

  return new ServiceContainer(serviceContext, serviceClasses);
}
