import type { ILogger } from '../types/logger.types';

/**
 * SSR Null Logger implementation
 */
export class SSRNullLogger implements ILogger {
  debug(): void {
    // No-op
  }

  info(): void {
    // No-op
  }

  warning(): void {
    // No-op
  }

  error(): void {
    // No-op
  }
}