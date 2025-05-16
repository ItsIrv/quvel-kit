import { LogLevel } from 'src/modules/Core/models/Logging/LogLevel';
import { LoggerInterface } from 'src/modules/Core/types/logging.types';

/**
 * Console logger implementation
 * Provides a simple PSR-3 inspired logger that outputs to the console
 */
export class ConsoleLogger implements LoggerInterface {
  /**
   * Mapping of log levels to console methods
   */
  private readonly levelMethods: Record<string, 'log' | 'error' | 'warn' | 'info' | 'debug'> = {
    [LogLevel.EMERGENCY]: 'error',
    [LogLevel.ALERT]: 'error',
    [LogLevel.CRITICAL]: 'error',
    [LogLevel.ERROR]: 'error',
    [LogLevel.WARNING]: 'warn',
    [LogLevel.NOTICE]: 'info',
    [LogLevel.INFO]: 'info',
    [LogLevel.DEBUG]: 'debug',
  };

  /**
   * Creates a new ConsoleLogger
   *
   * @param traceId - Optional trace ID to include with all log messages
   * @param context - Optional default context to include with all log messages
   */
  constructor(
    private readonly traceId?: string,
    private readonly defaultContext: Record<string, unknown> = {},
  ) {}

  /**
   * System is unusable
   */
  public emergency(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.EMERGENCY, message, context);
  }

  /**
   * Action must be taken immediately
   */
  public alert(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.ALERT, message, context);
  }

  /**
   * Critical conditions
   */
  public critical(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.CRITICAL, message, context);
  }

  /**
   * Runtime errors that do not require immediate action
   */
  public error(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.ERROR, message, context);
  }

  /**
   * Exceptional occurrences that are not errors
   */
  public warning(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.WARNING, message, context);
  }

  /**
   * Normal but significant events
   */
  public notice(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.NOTICE, message, context);
  }

  /**
   * Interesting events
   */
  public info(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.INFO, message, context);
  }

  /**
   * Detailed debug information
   */
  public debug(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.DEBUG, message, context);
  }

  /**
   * Logs with an arbitrary level
   */
  public log(level: string, message: string, context?: Record<string, unknown>): void {
    const timestamp = new Date().toISOString();
    const tracePrefix = this.traceId ? `[${this.traceId}] ` : '';
    const formattedMessage = `[${timestamp}] [${level.toUpperCase()}] ${tracePrefix}${message}`;

    // Merge default context with provided context
    const mergedContext = {
      ...this.defaultContext,
      ...context,
    };

    // Use the appropriate console method based on the level
    const method = this.levelMethods[level] || 'log';

    // Only pass context if it's not empty
    if (Object.keys(mergedContext).length > 0) {
      console[method](formattedMessage, mergedContext);
    } else {
      console[method](formattedMessage);
    }
  }
}
