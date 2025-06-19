import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { getAllServices as getAllCoreServices } from 'src/modules/Core/config/services';

export function getAllServices(): Map<string, ServiceClass> {
  const serviceClasses = new Map<string, ServiceClass>([
    // Core services (logging, configuration)
    ...getAllCoreServices(),

    // Add your services here
    // ['ExampleService', ExampleService],
  ]);

  return serviceClasses;
}
