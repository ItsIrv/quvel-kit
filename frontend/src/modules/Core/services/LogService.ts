import type { Service } from './Service';
import type { ServiceContainer } from './ServiceContainer';
import { ConsoleLogger } from './Logger/ConsoleLogger';
import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';

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
    if (typeof window !== 'undefined') {
      const traceInfo = (window as { __TRACE__?: TraceInfo }).__TRACE__;

      if (traceInfo) {
        this.traceInfo = traceInfo;
        this.traceInfo.runtime = 'client';
      }
    }

    this.logger = new ConsoleLogger(this.traceInfo.id);
  }

  /**
   * Registers the service with the container
   *
   * @param container - The service container
   */
  register(container: ServiceContainer): void {
    const config = container.config;

    if (config) {
      this.traceInfo.tenant = config.get('tenantId');
    }
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
