import { defineBoot } from '#q-app/wrappers';
import { ContainerKey, createContainer, setClientContainer } from 'src/services/ContainerService';

/**
 * Boot function to provide services globally.
 */
export default defineBoot(({ ssrContext, app }) => {
  const container = createContainer(ssrContext);

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    app.provide(ContainerKey, container);

    setClientContainer(app, container);
  }

  app.use(container.i18n.instance);
});
