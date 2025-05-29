/**
 * Minimal logger interface for SSR
 */
export interface ILogger {
  debug(message: string, data?: unknown): void;
  info(message: string, data?: unknown): void;
  warning(message: string, data?: unknown): void;
  error(message: string, data?: unknown): void;
}