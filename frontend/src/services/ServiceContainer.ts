import type { BootableService } from 'src/types/service.types';
import type { ApiService } from 'src/services/ApiService';
import type { I18nService } from 'src/services/I18nService';
import type { ValidationService } from 'src/services/ValidationService';
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
    readonly webSocket: WebSocketService,
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
