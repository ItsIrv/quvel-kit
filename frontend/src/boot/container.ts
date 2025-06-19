import { defineBoot } from '#q-app/wrappers';
import { ContainerKey, setClientContainer } from 'src/modules/Core/composables/useContainer';
import { createContainer } from 'src/modules/Core/utils/containerUtil';
import { getAllServices } from 'src/config/services';

/**
 * Boot function to provide the container service.
 */
export default defineBoot(({ ssrContext, app }) => {
  const container = createContainer(ssrContext, getAllServices());

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    app.provide(ContainerKey, container);

    setClientContainer(container);
  }

  app.use(container.i18n.instance);
});
