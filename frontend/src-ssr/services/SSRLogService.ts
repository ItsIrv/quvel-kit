import { SSRService } from './SSRService';
import type { SSRSingletonService } from '../types/service.types';
import type { ILogger } from '../types/logger.types';
import { LoggerFactory } from '../factories/LoggerFactory';

/**
 * SSR-specific logging service (Singleton)
 * Provides centralized logging for the SSR system
 * This is a stateless singleton service that doesn't store request-specific data
 */
export class SSRLogService extends SSRService implements SSRSingletonService, ILogger {
  private readonly logger: ILogger;

  constructor() {
    super();
    // Initialize logger immediately since this is a singleton
    this.logger = LoggerFactory.createFromEnv('SSR');
  }

  override register(): void {
    // Log initialization after service is registered
    this.logger.info('SSR Logger service registered', { 
      type: process.env.SSR_LOG_TYPE || 'console',
      level: process.env.SSR_LOG_LEVEL || 'info',
    });
  }

  // Implement ILogger interface by delegating to internal logger
  debug(message: string, data?: unknown): void {
    this.logger.debug(message, data);
  }

  info(message: string, data?: unknown): void {
    this.logger.info(message, data);
  }

  warning(message: string, data?: unknown): void {
    this.logger.warning(message, data);
  }

  error(message: string, data?: unknown): void {
    this.logger.error(message, data);
  }

  /**
   * Create a logger with a specific context
   */
  createLogger(context: string): ILogger {
    return LoggerFactory.createFromEnv(context);
  }
}