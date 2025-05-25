import { onBeforeUnmount } from 'vue';
import type { Service } from '../services/Service';
import { useContainer } from './useContainer';

/**
 * Provides a component-scoped service instance.
 * The service is created if missing and cleaned up on component unmount.
 */
export function useScopedService<T extends Service>(def: new () => T | (() => T)): T {
  const container = useContainer();
  const service = container.get(def as new () => T);

  const name = (service.constructor as new () => T).name;

  onBeforeUnmount(() => {
    container.removeService(name);
  });

  return service;
}
