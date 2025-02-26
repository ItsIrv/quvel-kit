import type { Ref } from 'vue';

/**
 * Represents the state of a task.
 */
export type TaskState = 'fresh' | 'active' | 'success' | 'error';

/**
 * Context for error handlers, allowing tracking and modification of error state.
 */
export interface ErrorHandlerContext<Err = unknown> {
  error: Err;
  errors: Ref<Record<string, unknown>>;
  i18n: {
    t: (key: string, params?: Record<string, unknown>) => string;
    te: (key: string) => boolean;
  }; // Provide translation helpers for translating response messages
  addError: (key: string, value: unknown) => void;
}

/**
 * Context for success handlers.
 */
export interface SuccessHandlerContext<Result = unknown> {
  result: Result;
}

/**
 * Base interface for handlers, supporting conditional execution via `matcher`.
 */
export interface Handler<Payload = unknown> {
  key?: string; // Finds key in payload
  matcher?: (payload: Payload) => boolean; // Runs function against returned value from key
}

/**
 * Defines an error handler that processes errors in a task.
 */
export interface ErrorHandler<Payload = unknown, Err = unknown> extends Handler<Payload> {
  callback: (payload: Payload, context: ErrorHandlerContext<Err>) => void;
}

/**
 * Defines a success handler that processes successful task execution.
 */
export interface SuccessHandler<Payload = unknown, Result = unknown> extends Handler<Payload> {
  callback: (payload: Payload, context: SuccessHandlerContext<Result>) => void;
}

/**
 * Allows defining a success callback or a list of success handlers.
 */
export type SuccessCallbackOrValue<Result = unknown, Payload = unknown> =
  | Array<SuccessHandler<Result>>
  | ((payload: Payload) => unknown);

/**
 * Allows defining an error callback or a list of error handlers.
 */
export type ErrorCallbackOrValue<Err = unknown> =
  | Array<ErrorHandler<Err>>
  | ((error: Err, context: ErrorHandlerContext<Err>) => unknown);

/**
 * Defines a generic value that can be a direct value or a function returning a value.
 */
export type Resolvable<T = unknown> = T | (() => T) | (() => Promise<T>);

/**
 * Defines the options available when creating a new task.
 */
export interface TaskOptions<Result = unknown, Payload = unknown> {
  /**
   * The primary function executed when the task runs.
   */
  task: ((payload: Payload) => Result) | ((payload: Payload) => Promise<Result>);

  /**
   * Optional payload resolver for the task function.
   */
  taskPayload?: Payload | (() => Payload) | (() => Promise<Payload>);

  /**
   * Function executed at the end of the task, regardless of success or failure.
   */
  always?: () => void | Promise<void>;

  /**
   * Defines whether to show toast notifications.
   */
  showNotification?: {
    success?: Resolvable<boolean | string>;
    error?: Resolvable<boolean | string>;
  };

  /**
   * Defines whether to show a loading indicator.
   */
  showLoading?: Resolvable<boolean>;

  /**
   * Configuration options for loading indicator.
   */
  loadingOptions?: object;

  /**
   * Controls whether the task should execute.
   */
  shouldRun?: Resolvable<boolean>;

  /**
   * Handlers executed on successful task execution.
   */
  successHandlers?: SuccessCallbackOrValue<Payload>;

  /**
   * Handlers executed when an error occurs.
   */
  errorHandlers?: ErrorCallbackOrValue;
}
