import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';
import { LogLevel } from 'src/modules/Core/models/Logging/LogLevel';
import { LoggerType } from 'src/modules/Core/models/Logging/LoggerType';
import { shouldLog } from 'src/modules/Core/utils/loggingUtil';

/**
 * SSR Console logger implementation that matches the frontend logging system
 */
export class SSRConsoleLogger implements LoggerInterface {
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

  constructor(
    private readonly traceInfo: TraceInfo,
    private readonly defaultContext: Record<string, unknown> = {},
  ) {}

  getTraceInfo(): TraceInfo {
    return this.traceInfo;
  }

  emergency(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.EMERGENCY, message, context);
  }

  alert(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.ALERT, message, context);
  }

  critical(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.CRITICAL, message, context);
  }

  error(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.ERROR, message, context);
  }

  warning(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.WARNING, message, context);
  }

  notice(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.NOTICE, message, context);
  }

  info(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.INFO, message, context);
  }

  debug(message: string, context?: Record<string, unknown>): void {
    this.log(LogLevel.DEBUG, message, context);
  }

  log(level: LogLevel, message: string, context?: Record<string, unknown>): void {
    if (!shouldLog(level)) {
      return;
    }

    const timestamp = new Date().toISOString();
    const tracePrefix = this.traceInfo ? `[${this.traceInfo.id}] ` : '';
    const tenantPrefix = this.traceInfo?.tenant ? `[${this.traceInfo.tenant}] ` : '';
    const formattedMessage = `[${timestamp}] [${level.toUpperCase()}] ${tracePrefix}${tenantPrefix}${message}`;

    const mergedContext = {
      ...this.defaultContext,
      ...context,
    };

    const method = this.levelMethods[level] || 'log';

    if (Object.keys(mergedContext).length > 0) {
      console[method](formattedMessage, mergedContext);
    } else {
      console[method](formattedMessage);
    }
  }
}

/**
 * SSR Null logger implementation
 */
export class SSRNullLogger implements LoggerInterface {
  constructor(private readonly traceInfo: TraceInfo) {}

  getTraceInfo(): TraceInfo {
    return this.traceInfo;
  }

  emergency(): void {}
  alert(): void {}
  critical(): void {}
  error(): void {}
  warning(): void {}
  notice(): void {}
  info(): void {}
  debug(): void {}
  log(): void {}
}

/**
 * Creates a logger instance for SSR based on environment configuration
 */
export function createSSRLogger(traceInfo: TraceInfo): LoggerInterface {
  const type = process.env.VITE_LOGGER || LoggerType.CONSOLE;

  switch (type.toLowerCase() as LoggerType) {
    case LoggerType.CONSOLE:
      return new SSRConsoleLogger(traceInfo, {
        runtime: 'server',
        environment: process.env.NODE_ENV || 'development',
      });
    case LoggerType.NULL:
    default:
      return new SSRNullLogger(traceInfo);
  }
}
