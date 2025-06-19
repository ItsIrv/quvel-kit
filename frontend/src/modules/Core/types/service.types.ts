import type { Request, Response } from 'express';
import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

/**
 * Interface for all services.
 */
export type Service = object;

/**
 * Constructor options for SSR-aware services.
 */
export interface SsrServiceOptions {
  req?: Request;
  res?: Response;
}

/**
 * Interface for services that support SSR context.
 */
export interface SsrAwareService extends Service {
  /**
   * Boot method will be called after service construction.
   * It will receive the SSR context if available.
   */
  boot(ssrServiceOptions?: SsrServiceOptions): void;
}

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

/**
 * Service class type.
 */
export type ServiceClass<T extends Service = Service> = new () => T;

/**
 * Service instance type.
 */
export type ServiceInstance = Service & Partial<RegisterService> & Partial<SsrAwareService>;
