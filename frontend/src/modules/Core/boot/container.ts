import { defineBoot } from '#q-app/wrappers';
import { ContainerKey, setClientContainer } from 'src/modules/Core/composables/useContainer';
import { createContainer } from 'src/modules/Core/utils/containerUtil';
import { getModuleServices } from 'src/modules/moduleRegistry';

/**
 * Boot function to provide the container service.
 */
export default defineBoot(({ ssrContext, app }) => {
  // Get services from all modules that need boot-time registration
  const moduleServices = getModuleServices();
  const serviceMap = new Map();

  for (const [name, ServiceClass] of Object.entries(moduleServices)) {
    serviceMap.set(name, ServiceClass);
  }

  const container = createContainer(ssrContext, serviceMap);

  if (ssrContext) {
    ssrContext.$container = container;
  } else {
    app.provide(ContainerKey, container);

    setClientContainer(container);
  }

  app.config.globalProperties.$container = container;
  app.use(container.i18n.instance);
});
