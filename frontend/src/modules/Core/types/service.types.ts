import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

/**
 * Interface for all services.
 */
export type Service = object;

/**
 * Interface for all services that integrate with the DI container.
 */
export interface RegisterService {
  /**
   * Runs initialization logic and injects the container.
   */
  register(container: ServiceContainer): void;
}

/**
 * Interface for services that can be shut down.
 */
export interface ShutdownService {
  /**
   * Runs shutdown logic.
   */
  shutdown(): void;
}
