import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';
import { ConsoleLogger } from '../services/Logger/ConsoleLogger';
import { NullLogger } from '../services/Logger/NullLogger';
import { LoggerType } from '../models/Logging/LoggerType';
import { LogLevel } from '../models/Logging/LogLevel';
import { SsrServiceOptions } from 'src/modules/Core/types/service.types';

/**
 * Creates a logger instance based on configuration
 *
 * @param type - Logger type to create
 * @param traceId - Optional trace ID to include with logs
 * @returns The appropriate logger implementation
 */
export function createLogger(
  ssrContext?: SsrServiceOptions,
  type: string = process.env.VITE_LOGGER || LoggerType.NULL,
): LoggerInterface {
  const traceInfo = ssrContext?.req?.traceInfo ??
    (typeof window !== 'undefined' ? (window as { __TRACE__?: TraceInfo }).__TRACE__ : null) ?? {
      id: '',
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV,
      tenant: ssrContext?.req?.tenantConfig?.tenantId ?? 'unknown',
      runtime: 'client',
    };

  switch (type.toLowerCase() as LoggerType) {
    case LoggerType.CONSOLE:
      return new ConsoleLogger(traceInfo);
    case LoggerType.NULL:
    default:
      return new NullLogger(traceInfo);
  }
}

/**
 * Log level priority mapping (higher number = higher priority)
 */
export const LOG_LEVEL_PRIORITY: Record<LogLevel, number> = {
  [LogLevel.EMERGENCY]: 8,
  [LogLevel.ALERT]: 7,
  [LogLevel.CRITICAL]: 6,
  [LogLevel.ERROR]: 5,
  [LogLevel.WARNING]: 4,
  [LogLevel.NOTICE]: 3,
  [LogLevel.INFO]: 2,
  [LogLevel.DEBUG]: 1,
};

/**
 * Gets the current log level from environment variables
 * Defaults to INFO if not specified or invalid
 */
export function getLogLevel(): LogLevel {
  const configLevel = process.env.VITE_LOG_LEVEL?.toLowerCase();

  if (configLevel && Object.values(LogLevel).includes(configLevel as LogLevel)) {
    return configLevel as LogLevel;
  }

  return LogLevel.INFO;
}

/**
 * Determines if a log level should be displayed based on the configured level
 *
 * @param level - The log level to check
 * @param configuredLevel - The configured minimum log level
 * @returns True if the level should be displayed
 */
export function shouldLog(level: LogLevel, configuredLevel: LogLevel = getLogLevel()): boolean {
  return LOG_LEVEL_PRIORITY[level] >= LOG_LEVEL_PRIORITY[configuredLevel];
}
