import { type App, inject } from 'vue';
import type { AxiosInstance } from 'axios';
import { createApi } from 'src/utils/axiosUtil';

interface ServiceContainer {
  api: AxiosInstance;
}

/**
 * Provides a scoped service container to the client.
 * Inject does not work in SSR, so do not attempt to do that. Use `ssrContext.container`.
 * @param app - The Vue App instance.
 * @param container  - The service container.
 */
export function provideContainer(app: App, container: ServiceContainer): void {
  app.provide('$container', container);
}

/**
 * Retrieves the service container.
 * If available `ssrContext.container` should be used instead.
 * The container in ssrContext could have more information
 * such as a logger with a trace-id, or data that was added throughout the lifecycle.
 * @returns The service container.
 */
export function useContainer(): ServiceContainer {
  if (typeof window === 'undefined') {
    return {
      api: createApi(),
    };
  } else {
    return inject('$container') as ServiceContainer;
  }
}
