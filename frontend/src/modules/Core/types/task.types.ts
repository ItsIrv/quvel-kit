import { ErrorBag, LaravelErrorResponse } from 'src/modules/Core/types/laravel.types';
import { I18nService } from '../services/I18nService';
import { AxiosError } from 'axios';

/**
 * Represents the state of a task.
 */
export type TaskState = 'fresh' | 'active' | 'success' | 'error';

/**
 * Context for error handlers, allowing tracking and modification of error state.
 * @template Err The error type of the task.
 */
export interface ErrorHandlerContext<Err = unknown> {
  error: Err;
  errors: ErrorBag;
  i18n: I18nService;
}

/**
 * Context for success handlers.
 * @template Result The result type of the task.
 */
export interface SuccessHandlerContext<Result = unknown> {
  result: Result;
}

/**
 * Base interface for handlers, supporting conditional execution via `matcher`.
 * @template Payload The payload type of the task.
 */
export interface Handler<Payload = unknown> {
  key?: string; // Finds key in payload
  matcher?: (payload: Payload) => boolean; // Runs function against returned value from key
}

/**
 * Defines an error handler that processes errors in a task.
 * @template Payload The payload type of the task.
 * @template Err The error type of the task.
 */
export interface ErrorHandler<Payload = unknown, Err = unknown> extends Handler<Payload> {
  callback: (payload: Payload, context: ErrorHandlerContext<Err>) => void;
}

/**
 * Defines a success handler that processes successful task execution.
 * @template Payload The payload type of the task.
 * @template Result The result type of the task.
 */
export interface SuccessHandler<Result = unknown, Payload = unknown> extends Handler<Payload> {
  callback: (payload: Payload, context: SuccessHandlerContext<Result>) => void;
}

/**
 * Allows defining a success callback or a list of success handlers.
 * @template Payload The payload type of the task.
 * @template Result The result type of the task.
 */
export type SuccessCallbackOrValue<Result = unknown, Payload = unknown> =
  | Array<SuccessHandler<Result, Payload>>
  | ((result: Result) => unknown);

/**
 * Allows defining an error callback or a list of error handlers.
 * @template Err The error type of the task.
 */
export type ErrorCallbackOrValue<Err = unknown> =
  | Array<ErrorHandler<Err>>
  | ((error: Err, context: ErrorHandlerContext<Err>) => unknown);

/**
 * Defines a generic value that can be a direct value or a function returning a value.
 * @template T The type of the value.
 */
export type Resolvable<T = unknown> = T | (() => T) | (() => Promise<T>);

/**
 * Defines the options available when creating a new task.
 *
 * @template Result The result type of the task.
 * @template Payload The payload type of the task.
 */
export interface TaskOptions<Result = unknown, Payload = unknown> {
  /**
   * The primary function executed when the task runs.
   *
   * @returns The result of the task function.
   */
  task: ((payload?: Payload) => Result) | ((payload?: Payload) => Promise<Result>);

  /**
   * Optional payload resolver for the task function.
   * Can be a direct value, a sync or async function.
   */
  taskPayload?: Payload | (() => Payload) | (() => Promise<Payload>);

  /**
   * Function executed at the end of the task, regardless of success or failure.
   */
  always?: () => void | Promise<void>;

  /**
   * Defines whether to show toast notifications.
   *
   * Does nothing on SSR.
   *
   * If true, gets message from task result, or the default message.
   * If a string, uses the string as the message.
   * If false, does nothing.
   */
  showNotification?: {
    success?: Resolvable<boolean | string>;
    error?: Resolvable<boolean | string>;
  };

  /**
   * Defines whether to show a loading indicator.
   *
   * Does nothing on SSR.
   */
  showLoading?: Resolvable<boolean>;

  /**
   * Configuration options for loading indicator.
   *
   * Does nothing on SSR.
   */
  loadingOptions?: object;

  /**
   * Controls whether the task should execute.
   */
  shouldRun?: Resolvable<boolean>;

  /**
   * Handlers executed on successful task execution.
   */
  successHandlers?: SuccessCallbackOrValue<Result>;

  /**
   * Handlers executed when an error occurs.
   */
  errorHandlers?: ErrorCallbackOrValue;

  /**
   * Whether to handle Laravel errors automatically.
   */
  handleLaravelError?:
    | boolean
    | {
        translate?: boolean;
        userCallBack?: ErrorHandlerCallback;
      };
}

type ErrorHandlerCallback = (
  err: AxiosError,
  context: ErrorHandlerContext<LaravelErrorResponse>,
) => void;
