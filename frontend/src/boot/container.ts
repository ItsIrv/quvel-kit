import { defineBoot } from '#q-app/wrappers';
import { ContainerKey, setClientContainer } from 'src/modules/Core/composables/useContainer';
import { createContainer } from 'src/modules/Core/utils/containerUtil';
import { CoreModule } from 'src/modules/Core';

/**
 * Boot function to provide the container service.
 */
export default defineBoot(({ ssrContext, app }) => {
  // Get core services for initialization
  const coreServices = CoreModule.services!();
  const serviceMap = new Map();

  for (const [name, ServiceClass] of Object.entries(coreServices)) {
    serviceMap.set(name, ServiceClass);
  }

  const container = createContainer(ssrContext, serviceMap);

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    app.provide(ContainerKey, container);

    setClientContainer(container);
  }

  app.use(container.i18n.instance);
});
