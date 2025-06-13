import type { Request, Response } from 'express';
import { SSRService } from '../services/SSRService';

/**
 * Service class constructor type
 */
export type SSRServiceClass<T extends SSRService = SSRService> = new () => T;

/**
 * Generic service class type
 */
export type SSRServiceClassGeneric = SSRServiceClass<SSRService>;

/**
 * Service instance type
 */
export type SSRServiceInstance = SSRService;

/**
 * SSR service options passed during scoped service boot
 */
export interface SSRServiceOptions {
  req?: Request;
  res?: Response;
}

/**
 * Interface for singleton services (stateless, shared across requests)
 * These services are registered once and reused across all requests
 */
export interface SSRSingletonService extends SSRService {
  register(container: unknown): void | Promise<void>;
}

/**
 * Interface for scoped services (request-specific instances)
 * These services are created per-request via container.scoped() method
 */
export interface SSRScopedService extends SSRService {
  register(container: unknown): void | Promise<void>;
  boot(options?: SSRServiceOptions): void | Promise<void>;
}