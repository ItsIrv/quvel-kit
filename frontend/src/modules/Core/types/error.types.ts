import { AxiosError } from 'axios';

/**
 * The generic error bag structure.
 * - Always a `Map<string, string>` for predictability.
 */
export type ErrorBag = Map<string, string>;

/**
 * Defines the Laravel error response structure.
 */
export type LaravelErrorResponse = AxiosError<{
  message?: string;
  errors?: Record<string, string | string[]>;
}>;
