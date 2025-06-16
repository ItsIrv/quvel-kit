import type {
  SSRServiceClass,
  SSRServiceInstance,
  SSRServiceOptions,
  SSRSingletonService,
  SSRScopedService,
  SSRServiceClassGeneric,
} from '../types/service.types';

/**
 * SSR Service Container
 * Manages SSR services without Core module dependencies
 */
export class SSRServiceContainer {
  private readonly services: Map<string, SSRServiceInstance> = new Map();
  private readonly serviceClassToKey: Map<SSRServiceClassGeneric, string> = new Map();

  constructor(serviceClasses: Map<string, SSRServiceClassGeneric> = new Map()) {
    this.initializeServices(serviceClasses);
    this.registerServices();
  }

  /**
   * Initialize services from their classes
   */
  private initializeServices(serviceClasses: Map<string, SSRServiceClassGeneric>): void {
    for (const [name, ServiceClass] of serviceClasses) {
      const instance = new ServiceClass();
      this.services.set(name, instance);
      // Store the mapping from class to key for lookup
      this.serviceClassToKey.set(ServiceClass, name);
    }
  }

  /**
   * Register all services
   */
  private registerServices(): void {
    for (const [name, service] of this.services) {
      if (this.isSingleton(service)) {
        try {
          void service.register(this);
        } catch (error) {
          throw new Error(
            `Service registration failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
          );
        }
      }
    }
  }

  /**
   * Get a service by its class
   */
  get<T extends SSRServiceInstance>(ServiceClass: SSRServiceClass<T>): T {
    // Look up the service key from our mapping
    const key = this.serviceClassToKey.get(ServiceClass as SSRServiceClassGeneric);
    if (key && this.services.has(key)) {
      return this.services.get(key) as T;
    }

    // Fallback to class name for backward compatibility
    const name = ServiceClass.name;
    if (this.services.has(name)) {
      return this.services.get(name) as T;
    }

    throw new Error(`Service ${name} not found in container`);
  }

  /**
   * Check if service is a singleton service
   */
  private isSingleton(service: SSRServiceInstance): service is SSRSingletonService {
    return (
      typeof (service as SSRSingletonService).register === 'function' &&
      typeof (service as SSRScopedService).boot !== 'function'
    );
  }

  /**
   * Check if service is a scoped service
   */
  private isScoped(service: SSRServiceInstance): service is SSRScopedService {
    return (
      typeof (service as SSRScopedService).register === 'function' &&
      typeof (service as SSRScopedService).boot === 'function'
    );
  }

  /**
   * Check if service exists
   */
  has(serviceName: string): boolean {
    return this.services.has(serviceName);
  }

  /**
   * Create a scoped instance of a service with request context
   * This is used for services that need request-specific state
   */
  scoped<T extends SSRServiceInstance>(
    ServiceClass: SSRServiceClass<T>,
    options?: SSRServiceOptions,
  ): T {
    // Create a new instance of the service
    const instance = new ServiceClass();

    // Register it with this container for dependency injection
    if (this.isSingleton(instance)) {
      void instance.register(this);
    }

    // Boot it with the provided options if it's a scoped service
    if (this.isScoped(instance) && options) {
      const result = instance.boot(options);
      if (result instanceof Promise) {
        console.warn(
          `Scoped service ${ServiceClass.name} has async boot method - this may cause issues`,
        );
      }
    }

    return instance;
  }
}
