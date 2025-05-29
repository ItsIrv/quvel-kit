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
 * SSR service options passed during boot
 */
export interface SSRServiceOptions {
  req?: Request;
  res?: Response;
}

/**
 * Interface for services that can be registered with the container
 */
export interface SSRRegisterService extends SSRService {
  register(container: unknown): void | Promise<void>;
}

/**
 * Interface for services that are SSR-aware and can receive request context
 */
export interface SSRSsrAwareService extends SSRService {
  boot(options?: SSRServiceOptions): void | Promise<void>;
}