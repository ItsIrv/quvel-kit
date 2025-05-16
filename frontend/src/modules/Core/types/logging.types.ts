/**
 * PSR-3 inspired logger interface
 * Defines standard logging methods that any logger implementation must provide
 */
export interface LoggerInterface {
  /**
   * Gets the current trace info
   */
  getTraceInfo(): TraceInfo;

  /**
   * System is unusable
   */
  emergency(message: string, context?: Record<string, unknown>): void;

  /**
   * Action must be taken immediately
   */
  alert(message: string, context?: Record<string, unknown>): void;

  /**
   * Critical conditions
   */
  critical(message: string, context?: Record<string, unknown>): void;

  /**
   * Runtime errors that do not require immediate action
   */
  error(message: string, context?: Record<string, unknown>): void;

  /**
   * Exceptional occurrences that are not errors
   */
  warning(message: string, context?: Record<string, unknown>): void;

  /**
   * Normal but significant events
   */
  notice(message: string, context?: Record<string, unknown>): void;

  /**
   * Interesting events
   */
  info(message: string, context?: Record<string, unknown>): void;

  /**
   * Detailed debug information
   */
  debug(message: string, context?: Record<string, unknown>): void;

  /**
   * Logs with an arbitrary level
   */
  log(level: string, message: string, context?: Record<string, unknown>): void;
}

/**
 * Trace information structure
 */
export interface TraceInfo {
  id: string;
  timestamp: string;
  environment: string;
  tenant?: string;
  runtime: 'server' | 'client' | 'native';
}
