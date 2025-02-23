import type { BootableService } from 'src/types/service.types';
import type { ApiService } from 'src/services/ApiService';
import type { I18nService } from 'src/services/I18nService';
import type { ValidationService } from 'src/services/ValidationService';
import type { TaskService } from './TaskService';
import type { Service } from './Service';

/**
 * The service container manages core services and allows dynamic service registration.
 */
export class ServiceContainer {
  private readonly bootedServiceNames = new Set<string>(); // Track booted service names

  constructor(
    readonly api: ApiService,
    readonly i18n: I18nService,
    readonly validation: ValidationService,
    readonly task: TaskService,
    private readonly services: Map<string, unknown> = new Map(),
  ) {
    this.bootServices();
    this.registerServices();
  }

  /**
   * Boots all core and dynamic services.
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
   * Registers all core and dynamic services.
   */
  private registerServices(): void {
    for (const service of [...Object.values(this), ...this.services.values()]) {
      if (this.isBootable(service)) {
        service.register?.();
      }
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
    this.bootService(name, service);

    if (this.isBootable(service)) {
      service.register?.();
    }

    return true;
  }

  /**
   * Boots a service only if it hasn't been booted yet.
   */
  private bootService(name: string, service: Service): void {
    if (this.isBootable(service) && !this.bootedServiceNames.has(name)) {
      this.bootedServiceNames.add(name);

      service.boot(this);
    }
  }

  /**
   * Type guard to check if a service implements `BootableService`.
   */
  private isBootable(service: unknown): service is BootableService {
    return typeof service === 'object' && service !== null && 'boot' in service;
  }
}
