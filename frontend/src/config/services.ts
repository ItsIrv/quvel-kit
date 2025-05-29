import type { ServiceClass } from 'src/modules/Core/types/service.types';
import { getAllServices as getAllCoreServices } from 'src/modules/Core/config/services';

export function getAllServices(): Map<string, ServiceClass> {
  // Create service container with service classes
  // The container will automatically:
  // 1. Instantiate each service
  // 2. Check if service has a boot method
  // 3. Pass SSR context only to services that need it
  // 4. Call register() on services that implement RegisterService
  const serviceClasses = new Map<string, ServiceClass>([...getAllCoreServices()]);

  return serviceClasses;
}
