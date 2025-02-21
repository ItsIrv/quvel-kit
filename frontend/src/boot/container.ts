import { defineBoot } from '#q-app/wrappers';
import { createContainer, provideContainer } from 'src/services/containerService';

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
