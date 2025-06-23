import type { ModuleLoader } from './types/module.types';
import { getAllServices } from './config/services';
import { ServiceClass } from './types/service.types';

/**
 * Core Module Loader
 *
 * Core is the only module that should expose services via services().
 * All other modules use lazy loading - services are created when container.get(Service) is called.
 */
export const CoreModule: ModuleLoader = {
  /**
   * Returns Core module services - the only module that should have this
   */
  services: () => {
    const serviceMap = getAllServices();
    const services: Record<string, ServiceClass> = {};

    for (const [name, ServiceClass] of serviceMap.entries()) {
      services[name] = ServiceClass;
    }

    return services;
  },
};
