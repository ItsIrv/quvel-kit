import { LoggerInterface, TraceInfo } from 'src/modules/Core/types/logging.types';
import { ConsoleLogger } from '../services/Logger/ConsoleLogger';
import { NullLogger } from '../services/Logger/NullLogger';
import { LoggerType } from '../models/Logging/LoggerType';
import { QSsrContext } from '@quasar/app-vite';

/**
 * Creates a logger instance based on configuration
 *
 * @param type - Logger type to create
 * @param traceId - Optional trace ID to include with logs
 * @returns The appropriate logger implementation
 */
export function createLogger(
  ssrContext?: QSsrContext | null,
  type: string = process.env.VITE_LOGGER || LoggerType.NULL,
): LoggerInterface {
  const traceInfo = ssrContext?.req?.__TRACE__ ??
    (window as { __TRACE__?: TraceInfo }).__TRACE__ ?? {
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
