import type { 
  SSRServiceClass, 
  SSRServiceInstance, 
  SSRServiceOptions,
  SSRRegisterService,
  SSRSsrAwareService,
  SSRServiceClassGeneric
} from '../types/service.types';

/**
 * SSR Service Container
 * Manages SSR services without Core module dependencies
 */
export class SSRServiceContainer {
  private readonly services: Map<string, SSRServiceInstance> = new Map();

  constructor(
    private readonly ssrServiceOptions?: SSRServiceOptions,
    serviceClasses: Map<string, SSRServiceClassGeneric> = new Map(),
  ) {
    this.initializeServices(serviceClasses);
    this.bootServices();
    this.registerServices();
  }

  /**
   * Initialize services from their classes
   */
  private initializeServices(serviceClasses: Map<string, SSRServiceClassGeneric>): void {
    for (const [name, ServiceClass] of serviceClasses) {
      const instance = new ServiceClass();
      this.services.set(name, instance);
    }
  }

  /**
   * Boot SSR-aware services with the SSR context
   */
  private bootServices(): void {
    for (const [name, service] of this.services) {
      if (this.hasBoot(service)) {
        try {
          const result = service.boot(this.ssrServiceOptions);
          if (result instanceof Promise) {
            // Handle async boot methods
            result.catch((error) => {
              console.error(`Failed to boot service ${name}:`, error);
            });
          }
        } catch (error) {
          console.error(`Failed to boot service ${name}:`, error);
          throw new Error(
            `Service boot failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
          );
        }
      }
    }
  }

  /**
   * Register all services
   */
  private registerServices(): void {
    for (const [name, service] of this.services) {
      if (this.isRegisterable(service)) {
        try {
          const result = service.register(this);
          if (result instanceof Promise) {
            // Handle async register methods
            result.catch((error) => {
              console.error(`Failed to register service ${name}:`, error);
            });
          }
        } catch (error) {
          console.error(`Failed to register service ${name}:`, error);
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
    const name = ServiceClass.name;
    if (this.services.has(name)) {
      return this.services.get(name) as T;
    }

    throw new Error(`Service ${name} not found in container`);
  }

  /**
   * Check if service has boot method
   */
  private hasBoot(service: SSRServiceInstance): service is SSRSsrAwareService {
    return typeof (service as SSRSsrAwareService).boot === 'function';
  }

  /**
   * Check if service has register method
   */
  private isRegisterable(service: SSRServiceInstance): service is SSRRegisterService {
    return typeof (service as SSRRegisterService).register === 'function';
  }

  /**
   * Get all service names
   */
  getServiceNames(): string[] {
    return Array.from(this.services.keys());
  }

  /**
   * Check if service exists
   */
  has(serviceName: string): boolean {
    return this.services.has(serviceName);
  }

  /**
   * Destroy all services
   */
  destroy(): void {
    for (const [name, service] of this.services) {
      if (typeof service.destroy === 'function') {
        try {
          const result = service.destroy();
          if (result instanceof Promise) {
            result.catch((error) => {
              console.error(`Failed to destroy service ${name}:`, error);
            });
          }
        } catch (error) {
          console.error(`Failed to destroy service ${name}:`, error);
        }
      }
    }
  }
}