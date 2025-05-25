/* eslint-disable @typescript-eslint/no-unused-vars */
import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';

/**
 * Null logger implementation that discards all log messages
 * Used in production or when logging is disabled
 */
export class NullLogger implements LoggerInterface {
  /**
   * Creates a new NullLogger
   */
  constructor(private readonly traceInfo: TraceInfo) {}

  /**
   * Gets the current trace info
   */
  public getTraceInfo(): TraceInfo {
    return this.traceInfo;
  }

  /**
   * Discards emergency message
   */
  emergency(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards alert message
   */
  alert(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards critical message
   */
  critical(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards error message
   */
  error(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards warning message
   */
  warning(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards notice message
   */
  notice(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards info message
   */
  info(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards debug message
   */
  debug(message: string, context?: Record<string, unknown>): void {
    // No-op
  }

  /**
   * Discards log message
   */
  log(level: string, message: string, context?: Record<string, unknown>): void {
    // No-op
  }
}
