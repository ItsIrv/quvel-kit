import { LogLevel } from 'src/modules/Core/models/Logging/LogLevel';
import type { ILogger } from '../types/logger.types';

/**
 * SSR Console Logger implementation
 */
export class SSRConsoleLogger implements ILogger {
  constructor(
    private level: LogLevel,
    private context?: string,
  ) {}

  private shouldLog(level: LogLevel): boolean {
    const levels = [
      LogLevel.DEBUG,
      LogLevel.INFO,
      LogLevel.NOTICE,
      LogLevel.WARNING,
      LogLevel.ERROR,
      LogLevel.CRITICAL,
      LogLevel.ALERT,
      LogLevel.EMERGENCY,
    ];
    const currentIndex = levels.indexOf(this.level);
    const requestedIndex = levels.indexOf(level);
    return requestedIndex >= currentIndex;
  }

  private formatMessage(level: string, message: string, data?: unknown): string {
    const timestamp = new Date().toISOString();
    const contextStr = this.context ? ` [${this.context}]` : '';
    const dataStr = data ? ` ${JSON.stringify(data)}` : '';
    return `[${timestamp}] ${level}${contextStr}: ${message}${dataStr}`;
  }

  debug(message: string, data?: unknown): void {
    if (this.shouldLog(LogLevel.DEBUG)) {
      console.debug(this.formatMessage('DEBUG', message, data));
    }
  }

  info(message: string, data?: unknown): void {
    if (this.shouldLog(LogLevel.INFO)) {
      console.info(this.formatMessage('INFO', message, data));
    }
  }

  warning(message: string, data?: unknown): void {
    if (this.shouldLog(LogLevel.WARNING)) {
      console.warn(this.formatMessage('WARNING', message, data));
    }
  }

  error(message: string, data?: unknown): void {
    if (this.shouldLog(LogLevel.ERROR)) {
      console.error(this.formatMessage('ERROR', message, data));
    }
  }
}