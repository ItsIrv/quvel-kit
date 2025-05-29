/**
 * Base SSR Service class
 * Provides base functionality for SSR services without Core module dependencies
 */
export abstract class SSRService {
  /**
   * Service name for identification
   */
  get name(): string {
    return this.constructor.name;
  }

  /**
   * Boot method called during service container initialization
   * Override this method to perform initialization that doesn't require other services
   */
  boot?(): void | Promise<void>;

  /**
   * Register method called after all services are instantiated
   * Override this method to register dependencies with other services
   */
  register?(container: unknown): void | Promise<void>;

  /**
   * Destroy method called during service container shutdown
   * Override this method to perform cleanup
   */
  destroy?(): void | Promise<void>;
}
