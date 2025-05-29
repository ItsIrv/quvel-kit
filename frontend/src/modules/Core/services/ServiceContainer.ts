import type { RegisterService, SsrAwareService } from 'src/modules/Core/types/service.types';
import type { Service } from './Service';
import type { SsrServiceOptions } from 'src/modules/Core/types/service.types';
import type { ConfigService } from './ConfigService';
import type { LogService } from './LogService';
import { ApiService } from './ApiService';
import { I18nService } from './I18nService';
import { ValidationService } from './ValidationService';
import { TaskService } from './TaskService';
import { WebSocketService } from './WebSocketService';
import type { ServiceClass, ServiceInstance } from 'src/modules/Core/types/service.types';

/**
 * The service container manages core services and allows dynamic service registration.
 */
export class ServiceContainer {
  private readonly services: Map<string, ServiceInstance> = new Map();
  private readonly serviceClassToKey: Map<ServiceClass, string> = new Map();

  constructor(
    private readonly ssrServiceOptions?: SsrServiceOptions,
    serviceClasses: Map<string, ServiceClass> = new Map(),
  ) {
    this.initializeServices(serviceClasses);
    this.bootServices();
    this.registerServices();
  }

  /**
   * Initialize services from their classes without any context.
   */
  private initializeServices(serviceClasses: Map<string, ServiceClass>): void {
    for (const [name, ServiceClass] of serviceClasses) {
      const instance = new ServiceClass();
      this.services.set(name, instance);
      // Store the mapping from class to key for lookup
      this.serviceClassToKey.set(ServiceClass, name);
    }
  }

  /**
   * Boot SSR-aware services with the SSR context.
   */
  private bootServices(): void {
    for (const [name, service] of this.services) {
      if (this.hasBoot(service)) {
        try {
          service.boot(this.ssrServiceOptions);
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
   * Registers all core and dynamic services.
   */
  private registerServices(): void {
    // After all services are instantiated, call register on those that support it
    for (const [name, service] of this.services) {
      if (this.isRegisterable(service)) {
        try {
          service.register(this);
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
   * Retrieves a service by its class.
   *
   * @param ServiceClass - The service class constructor.
   */
  get<T extends Service>(ServiceClass: ServiceClass<T>): T {
    // Look up the service key from our mapping
    const key = this.serviceClassToKey.get(ServiceClass);
    if (key && this.services.has(key)) {
      return this.services.get(key) as T;
    }

    // Fallback to class name for backward compatibility
    const name = ServiceClass.name;
    if (this.services.has(name)) {
      return this.services.get(name) as T;
    }

    // Lazy initialization
    const instance = new ServiceClass() as ServiceInstance;
    this.services.set(name, instance);

    // Boot if has boot method
    if (this.hasBoot(instance)) {
      try {
        instance.boot(this.ssrServiceOptions);
      } catch (error) {
        console.error(`Failed to boot service ${name}:`, error);
        throw new Error(
          `Service boot failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
        );
      }
    }

    // Register if supported
    if (this.isRegisterable(instance)) {
      try {
        instance.register(this);
      } catch (error) {
        console.error(`Failed to register service ${name}:`, error);
        throw new Error(
          `Service registration failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
        );
      }
    }

    return instance as T;
  }

  /**
   * Get a service by name.
   */
  getByName<T extends Service>(name: string): T | undefined {
    return this.services.get(name) as T | undefined;
  }

  /**
   * Adds a new service by class.
   */
  addService<T extends Service>(ServiceClass: ServiceClass<T>, overwrite = false): boolean {
    const name = ServiceClass.name;

    if (this.services.has(name) && !overwrite) {
      return false;
    }

    const instance = new ServiceClass() as ServiceInstance;
    this.services.set(name, instance);

    // Boot if has boot method call it regardless of context because it may be used in non-SSR context
    if (this.hasBoot(instance)) {
      try {
        instance.boot(this.ssrServiceOptions);
      } catch (error) {
        console.error(`Failed to boot service ${name}:`, error);
        throw new Error(
          `Service boot failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
        );
      }
    }

    // Register if supported
    if (this.isRegisterable(instance)) {
      try {
        instance.register(this);
      } catch (error) {
        console.error(`Failed to register service ${name}:`, error);
        throw new Error(
          `Service registration failed for ${name}: ${error instanceof Error ? error.message : String(error)}`,
        );
      }
    }

    return true;
  }

  /**
   * Checks if a service exists by class.
   */
  hasService<T extends Service>(ServiceClass: ServiceClass<T>): boolean {
    return this.services.has(ServiceClass.name);
  }

  /**
   * Removes a service and shuts it down if possible.
   * This allows services to perform any necessary cleanup.
   */
  removeService(name: string): boolean {
    const service = this.services.get(name);
    if (this.isShutdownable(service)) {
      service.shutdown();
    }

    this.services.delete(name);
    return true;
  }

  /**
   * Type guard to check if a service has a boot method (SSR-aware).
   */
  private hasBoot(service: unknown): service is SsrAwareService {
    return (
      typeof service === 'object' &&
      service !== null &&
      'boot' in service &&
      typeof (service as SsrAwareService).boot === 'function'
    );
  }

  /**
   * Checks if a service implements `shutdown` method.
   */
  private isShutdownable(service: unknown): service is { shutdown: () => void } {
    return typeof service === 'object' && service !== null && 'shutdown' in service;
  }

  /**
   * Type guard to check if a service implements `RegisterService`.
   */
  private isRegisterable(service: unknown): service is RegisterService {
    return typeof service === 'object' && service !== null && 'register' in service;
  }

  // Convenience getters for core services
  get config(): ConfigService {
    return this.getByName<ConfigService>('ConfigService')!;
  }

  get log(): LogService {
    return this.getByName<LogService>('LogService')!;
  }

  get api(): ApiService {
    return this.get(ApiService);
  }

  get i18n(): I18nService {
    return this.get(I18nService);
  }

  get validation(): ValidationService {
    return this.get(ValidationService);
  }

  get task(): TaskService {
    return this.get(TaskService);
  }

  get ws(): WebSocketService {
    return this.get(WebSocketService);
  }
}
