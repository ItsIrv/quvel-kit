import { onBeforeUnmount } from 'vue';
import type { Service } from '../services/Service';
import { useContainer } from './useContainer';

/**
 * Type guard to distinguish constructors vs factory functions.
 */
function isConstructor<T>(fn: unknown): fn is new () => T {
  return typeof fn === 'function' && /^class\s/.test(Function.prototype.toString.call(fn));
}
/**
 * Provides a component-scoped service instance.
 * The service is created if missing, and cleaned up on component unmount.
 */
export function useScopedService<T extends Service>(
  serviceDefinition: (new () => T) | (() => T),
  nameOverride?: string,
): T {
  const container = useContainer();

  // Check if we have a constructor or factory
  const isCtorFn = isConstructor<T>(serviceDefinition);

  let name: string;
  if (isCtorFn) {
    name = nameOverride ?? (serviceDefinition as new () => T).name;
  } else if (nameOverride) {
    name = nameOverride;
  } else {
    throw new Error('Factory services must provide a name');
  }

  // Get or create service based on type
  const service = isCtorFn
    ? container.getOrCreateService(serviceDefinition as new () => T)
    : container.getOrCreateService(serviceDefinition as () => T, name);

  // Clean up service when component unmounts
  onBeforeUnmount(() => {
    container.removeService(name);
  });

  return service;
}
