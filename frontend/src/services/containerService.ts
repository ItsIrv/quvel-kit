import { type App, inject } from 'vue';
import { createApi } from 'src/utils/axiosUtil';
import type { ServiceContainer } from 'src/types/container.types';
import type { I18nType } from 'src/types/i18n.types';
import { createI18n } from 'vue-i18n';
import messages from 'src/i18n';

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
 * such as a configured i18n with the users language already set.
 * @returns The service container.
 */
export function useContainer(): ServiceContainer {
  if (typeof window === 'undefined') {
    const i18n: I18nType = createI18n({
      locale: 'en-US',
      legacy: false,
      messages,
    });

    return {
      api: createApi(),
      i18n,
    };
  } else {
    return inject('$container') as ServiceContainer;
  }
}
