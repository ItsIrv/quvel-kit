import type { Service } from './Service';
import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';
import { createLogger } from 'src/modules/Core/utils/loggerUtil';

/**
 * Service for application logging and tracing
 * Provides standardized logging capabilities and trace context management
 */
export class LogService implements Service {
  private logger: LoggerInterface;

  /**
   * Creates a new LogService instance
   */
  constructor(private readonly traceInfo: TraceInfo) {
    this.logger = createLogger(process.env.VITE_LOGGER, this.traceInfo.id);
  }

  /**
   * Gets the current trace info
   */
  getTraceInfo(): TraceInfo {
    return this.traceInfo;
  }

  /**
   * Gets the current trace ID
   */
  getTraceId(): string {
    return this.traceInfo.id;
  }

  /**
   * Gets the current logger instance
   */
  getLogger(): LoggerInterface {
    return this.logger;
  }

  /**
   * Sets a custom logger implementation
   *
   * @param logger - Custom logger implementation
   */
  setLogger(logger: LoggerInterface): void {
    this.logger = logger;
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
