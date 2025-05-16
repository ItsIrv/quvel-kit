import { LoggerInterface } from 'src/modules/Core/types/logging.types';
import { ConsoleLogger } from '../services/Logger/ConsoleLogger';
import { NullLogger } from '../services/Logger/NullLogger';
import { LoggerType } from '../models/Logging/LoggerType';

/**
 * Creates a logger instance based on configuration
 *
 * @param type - Logger type to create
 * @param traceId - Optional trace ID to include with logs
 * @returns The appropriate logger implementation
 */
export function createLogger(
  type: string = process.env.VITE_LOGGER || LoggerType.NULL,
  traceId?: string,
): LoggerInterface {
  switch (type.toLowerCase() as LoggerType) {
    case LoggerType.CONSOLE:
      return new ConsoleLogger(traceId);
    case LoggerType.NULL:
    default:
      return new NullLogger();
  }
}
