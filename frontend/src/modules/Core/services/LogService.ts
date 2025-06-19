import type { SsrAwareService, SsrServiceOptions } from '../types/service.types';
import { LoggerInterface } from 'src/modules/Core/types/logging.types';
import { createLogger } from '../utils/loggingUtil';
import { Service } from './Service';

/**
 * Service for application logging and tracing
 * Provides standardized logging capabilities and trace context management
 */
export class LogService extends Service implements SsrAwareService {
  private logger!: LoggerInterface;

  boot(ssrServiceOptions?: SsrServiceOptions): void {
    this.logger = createLogger(ssrServiceOptions);
  }

  /**
   * Gets the current logger instance
   */
  getLogger(): LoggerInterface {
    return this.logger;
  }

  // Proxy logger methods for convenience
  emergency(message: string, context?: Record<string, unknown>): void {
    this.logger.emergency(message, context);
  }

  alert(message: string, context?: Record<string, unknown>): void {
    this.logger.alert(message, context);
  }

  critical(message: string, context?: Record<string, unknown>): void {
    this.logger.critical(message, context);
  }

  error(message: string, context?: Record<string, unknown>): void {
    this.logger.error(message, context);
  }

  warning(message: string, context?: Record<string, unknown>): void {
    this.logger.warning(message, context);
  }

  notice(message: string, context?: Record<string, unknown>): void {
    this.logger.notice(message, context);
  }

  info(message: string, context?: Record<string, unknown>): void {
    this.logger.info(message, context);
  }

  debug(message: string, context?: Record<string, unknown>): void {
    this.logger.debug(message, context);
  }
}
