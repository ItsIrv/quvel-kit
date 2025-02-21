import { defineBoot } from '#q-app/wrappers';
import { createApi } from 'src/utils/axiosUtil';
import type { QSsrContext } from '@quasar/app-vite';
import type { ServiceContainer } from 'src/types/container.types';
import { provideContainer } from 'src/services/containerService';
import { createI18n } from 'vue-i18n';
import messages from 'src/i18n';
import type { I18nType } from 'src/types/i18n.types';

/**
 * Creates the service container per request.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns The service container.
 */
function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  const i18n: I18nType = createI18n({
    locale: 'en-US',
    legacy: false,
    messages,
  });

  return {
    api: createApi(ssrContext),
    i18n,
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

  app.use(container.i18n);
});
