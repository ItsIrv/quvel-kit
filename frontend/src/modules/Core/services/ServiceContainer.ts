import type { RegisterService } from 'src/modules/Core/types/service.types';
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
  }

  /**
   * Registers all core and dynamic services.
   */
  private registerServices(): void {
    for (const [name, service] of Object.entries({
      ...this,
      ...Object.fromEntries(this.services),
    })) {
      this.registerService(name, service as RegisterService);
    }
  }

  /**
   * Retrieves or lazily creates a service.
   *
   * @param def - A class constructor (auto-name) or factory function (must be bootable).
   */
  get<T extends Service>(def: new () => T): T;
  get<T extends Service>(def: () => T): T;
  get<T extends Service>(def: (() => T) | (new () => T)): T {
    const name = typeof def === 'function' && 'prototype' in def ? def.name : null;

    if (name && this.services.has(name)) {
      return this.services.get(name) as T;
    }

    const instance: T = name ? new (def as new () => T)() : (def as () => T)();

    const serviceName = name ?? (instance.constructor as new () => T).name;

    this.services.set(serviceName, instance);

    if (this.isBootable(instance)) {
      instance.register(this);
    }

    return instance;
  }

  /**
   * Adds a new service instance by class or factory.
   */
  addService<T extends Service>(
    def: new () => T | (() => T),
    service: T,
    overwrite = false,
  ): boolean {
    const name =
      typeof def === 'function' && 'prototype' in def
        ? (def as new () => T).name
        : (service.constructor as new () => T).name;

    if (this.services.has(name) && !overwrite) {
      return false;
    }

    this.services.set(name, service);

    if (this.isBootable(service)) {
      service.register(this);
    }

    return true;
  }

  /**
   * Checks if a service exists by class or factory definition.
   */
  hasService<T extends Service>(def: new () => T | (() => T)): boolean {
    const name = typeof def === 'function' && 'prototype' in def ? (def as new () => T).name : null;

    return name ? this.services.has(name) : false;
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
   * Registers a service only if it hasn't been registered.
   */
  private registerService(name: string, service: Service): void {
    if (this.isBootable(service)) {
      service.register(this);
    }
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
  private isBootable(service: unknown): service is RegisterService {
    return typeof service === 'object' && service !== null && 'register' in service;
  }
}
