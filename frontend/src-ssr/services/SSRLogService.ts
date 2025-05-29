import { SSRService } from './SSRService';
import type { SSRSsrAwareService } from '../types/service.types';
import type { ILogger } from '../types/logger.types';
import { LoggerFactory } from '../factories/LoggerFactory';
import { LoggerType } from 'src/modules/Core/models/Logging/LoggerType';
import { LogLevel } from 'src/modules/Core/models/Logging/LogLevel';

/**
 * SSR-specific logging service
 * Provides centralized logging for the SSR system
 */
export class SSRLogService extends SSRService implements SSRSsrAwareService, ILogger {
  private logger: ILogger;

  constructor() {
    super();
    // Start with a null logger until boot is called
    this.logger = LoggerFactory.createLogger(LoggerType.NULL, LogLevel.INFO);
  }

  override boot(): void {
    // Create logger from environment configuration
    this.logger = LoggerFactory.createFromEnv('SSR');
    this.logger.info('SSR Logger initialized', { 
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