import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

/**
 * Interface for all services.
 */
export type Service = object;

/**
 * Interface for all services that integrate with the DI container.
 */
export interface BootableService {
  /**
   * Runs initialization logic and injects the container.
   */
  register(container: ServiceContainer): void;

  /**
   * (Optional) Runs any extra registration logic after booting.
   */
  boot?(): void;
}
