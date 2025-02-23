import type { ServiceContainer } from 'src/services/ServiceContainer';

/**
 * Interface for all services that integrate with the DI container.
 */
export interface BootableService {
  /**
   * Runs initialization logic and injects the container.
   */
  boot(container: ServiceContainer): void;

  /**
   * (Optional) Runs any extra registration logic after booting.
   */
  register?(): void;
}
