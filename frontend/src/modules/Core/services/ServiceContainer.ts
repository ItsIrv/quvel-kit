import type { BootableService } from 'src/modules/Core/types/service.types';
import type { ApiService } from 'src/modules/Core/services/ApiService';
import type { I18nService } from 'src/modules/Core/services/I18nService';
import type { ValidationService } from 'src/modules/Core/services/ValidationService';
import type { TaskService } from './TaskService';
import type { Service } from './Service';
import { ConfigService } from './ConfigService';
import { WebSocketService } from './WebSocketService';

/**
 * The service container manages core services and allows dynamic service registration.
 */
export class ServiceContainer {
  private readonly registeredServices = new Set<string>(); // Track registered services
  private readonly bootedServices = new Set<string>(); // Track booted services

  constructor(
    readonly config: ConfigService,
    readonly api: ApiService,
    readonly i18n: I18nService,
    readonly validation: ValidationService,
    readonly task: TaskService,
    readonly ws: WebSocketService,
    private readonly services: Map<string, unknown> = new Map(),
  ) {
    this.registerServices();
    this.bootServices();
  }

  /**
   * Registers all core and dynamic services.
   */
  private registerServices(): void {
    for (const [name, service] of Object.entries({
      ...this,
      ...Object.fromEntries(this.services),
    })) {
      this.registerService(name, service as BootableService);
    }
  }

  /**
   * Boots all registered services.
   */
  private bootServices(): void {
    for (const [name, service] of Object.entries({
      ...this,
      ...Object.fromEntries(this.services),
    })) {
      this.bootService(name, service as BootableService);
    }
  }

  /**
   * Retrieves a registered dynamic service.
   */
  getService<T>(name: string): T | undefined {
    return this.services.get(name) as T | undefined;
  }

  /**
   * Checks if a dynamic service exists.
   */
  hasService(name: string): boolean {
    return this.services.has(name);
  }

  /**
   * Adds a new dynamic service.
   */
  addService<T>(name: string, service: T & Service, overwrite = false): boolean {
    if (this.services.has(name) && !overwrite) {
      return false;
    }

    this.services.set(name, service);
    this.registerService(name, service);
    this.bootService(name, service);

    return true;
  }

  /**
   * Gets a service if it exists, or creates and registers it safely.
   */
  getOrCreateService<T extends Service>(serviceFactory: () => T, name: string): T;
  getOrCreateService<T extends Service>(ServiceClass: new () => T): T;
  getOrCreateService<T extends Service>(arg1: (() => T) | (new () => T), arg2?: string): T {
    // Determine if we're dealing with a factory function or class constructor
    const isFactory = typeof arg2 === 'string';

    // Determine the service name
    const name = isFactory ? arg2 : (arg1 as new () => T).name;

    // Check if service already exists
    let service = this.getService<T>(name);
    if (service) return service;

    // Create new service instance
    service = isFactory ? (arg1 as () => T)() : new (arg1 as new () => T)();

    // Store the service
    this.services.set(name, service);

    // Handle registration and boot if applicable
    if (this.isBootable(service)) {
      if (!this.registeredServices.has(name)) {
        this.registeredServices.add(name);
        service.register(this);
      }

      if (!this.bootedServices.has(name) && service.boot) {
        this.bootedServices.add(name);
        service.boot();
      }
    }

    return service;
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
    this.registeredServices.delete(name);
    this.bootedServices.delete(name);
    return true;
  }

  /**
   * Checks if a service implements `shutdown` method.
   */
  private isShutdownable(service: unknown): service is { shutdown: () => void } {
    return typeof service === 'object' && service !== null && 'shutdown' in service;
  }

  /**
   * Registers a service only if it hasn't been registered.
   */
  private registerService(name: string, service: Service): void {
    if (this.isBootable(service) && !this.registeredServices.has(name)) {
      this.registeredServices.add(name);
      service.register(this);
    }
  }

  /**
   * Boots a service only if it hasn't been booted yet.
   */
  private bootService(name: string, service: Service): void {
    if (this.isBootable(service) && !this.bootedServices.has(name)) {
      this.bootedServices.add(name);
      service.boot?.();
    }
  }

  /**
   * Type guard to check if a service implements `BootableService`.
   */
  private isBootable(service: unknown): service is BootableService {
    return typeof service === 'object' && service !== null && 'register' in service;
  }
}
