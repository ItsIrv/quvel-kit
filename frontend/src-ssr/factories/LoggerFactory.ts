import { LogLevel } from 'src/modules/Core/models/Logging/LogLevel';
import { LoggerType } from 'src/modules/Core/models/Logging/LoggerType';
import type { ILogger } from '../types/logger.types';
import { SSRConsoleLogger } from '../loggers/SSRConsoleLogger';
import { SSRNullLogger } from '../loggers/SSRNullLogger';

/**
 * Factory for creating logger instances
 */
export class LoggerFactory {
  /**
   * Create a logger instance based on type and level
   */
  static createLogger(type: LoggerType, level: LogLevel, context?: string): ILogger {
    switch (type) {
      case LoggerType.CONSOLE:
        return new SSRConsoleLogger(level, context);
      case LoggerType.NULL:
        return new SSRNullLogger();
      default:
        return new SSRConsoleLogger(level, context);
    }
  }

  /**
   * Create a logger from environment variables
   */
  static createFromEnv(context?: string): ILogger {
    const loggerType = (process.env.SSR_LOG_TYPE || LoggerType.CONSOLE) as LoggerType;
    const logLevel = (process.env.SSR_LOG_LEVEL || LogLevel.INFO) as LogLevel;
    
    return LoggerFactory.createLogger(loggerType, logLevel, context);
  }
}