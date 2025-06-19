import type {
  ErrorCallbackOrValue,
  ErrorHandler,
  ErrorHandlerContext,
  Resolvable,
  SuccessCallbackOrValue,
  SuccessHandler,
  SuccessHandlerContext,
  TaskOptions,
  TaskState,
} from 'src/modules/Core/types/task.types';
import { computed, ComputedRef, ref, type Ref } from 'vue';
import { showNotification } from 'src/modules/Core/utils/notifyUtil';
import { getSafe, resolveValue } from 'src/modules/Core/utils/objectUtils';
import { hideLoading, showLoading } from 'src/modules/Core/utils/loadingUtil';
import type { RegisterService } from 'src/modules/Core/types/service.types';
import { Service } from './Service';
import type { ServiceContainer } from './ServiceContainer';
import { LaravelErrorHandler } from 'src/modules/Core/utils/errorUtil';
import { ErrorBag } from 'src/modules/Core/types/laravel.types';
import { I18nService } from './I18nService';

/**
 * Task Service - Manages async operations with built-in error handling, notifications, and loading.
 */
export class TaskService extends Service implements RegisterService {
  private i18n!: I18nService;

  /**
   * Common error handlers.
   */
  readonly errorHandlers = Object.freeze({
    Laravel: LaravelErrorHandler,
  });

  /**
   * Injects the service container dependencies.
   */
  public register(container: ServiceContainer): void {
    this.i18n = container.i18n;
  }

  /**
   * Creates a new managed task.
   */
  public newTask<Result = unknown, Payload = unknown>(
    options: TaskOptions<Result, Payload>,
    mutableProps: (keyof TaskOptions<Result, Payload>)[] = [],
  ): {
    isActive: ComputedRef<boolean>;
    run: (customOptions?: Partial<TaskOptions<Result, Payload>>) => Promise<Result | false>;
    reset: () => void;
    state: Ref<TaskState>;
    error: Ref<unknown>;
    errors: Ref<ErrorBag>;
    result: Ref<Result | undefined>;
  } {
    const currentError = ref<unknown>();
    const currentErrors = ref<ErrorBag>(new Map());
    const currentResult = ref<Result>();
    const currentState = ref<TaskState>('fresh');

    const isActive = computed(() => currentState.value === 'active');

    // Create bound methods with proper type preservation
    const run = async (
      customOptions?: Partial<TaskOptions<Result, Payload>>,
    ): Promise<Result | false> => {
      return this.runTask(
        options,
        mutableProps,
        currentError,
        currentErrors,
        currentResult,
        currentState,
        customOptions,
      );
    };

    const reset = (): void => {
      this.resetTask(currentError, currentErrors, currentResult, currentState);
    };

    return {
      isActive,
      run,
      reset,
      state: currentState,
      error: currentError,
      errors: currentErrors,
      result: currentResult,
    };
  }

  /**
   * Resets the task state.
   */
  private resetTask(
    currentError: Ref<unknown>,
    currentErrors: Ref<ErrorBag>,
    currentResult: Ref<unknown>,
    currentState: Ref<TaskState>,
  ): void {
    currentError.value = undefined;
    currentErrors.value = new Map();
    currentResult.value = undefined;
    currentState.value = 'fresh';
  }

  /**
   * Runs the task.
   */
  private async runTask<Result = unknown, Payload = unknown>(
    options: TaskOptions<Result, Payload>,
    mutableProps: (keyof TaskOptions<Result, Payload>)[],
    currentError: Ref<unknown>,
    currentErrors: Ref<ErrorBag>,
    currentResult: Ref<Result | undefined>,
    currentState: Ref<TaskState>,
    customOptions?: Partial<TaskOptions<Result, Payload>>,
  ): Promise<Result | false> {
    this.resetTask(currentError, currentErrors, currentResult as Ref<unknown>, currentState);

    currentState.value = 'active';

    // Merge options
    const taskOptions = this.mergeTaskOptions(options, mutableProps, customOptions);

    // Check if task should run
    if ((await resolveValue(taskOptions.shouldRun)) === false) {
      return false;
    }

    // Handle loading indicator
    const shouldShowLoading = await resolveValue(taskOptions.showLoading);
    if (shouldShowLoading === true) {
      showLoading(0, taskOptions.loadingOptions);
    }

    try {
      const result = await taskOptions.task(await resolveValue(taskOptions.taskPayload));

      currentResult.value = result;
      currentState.value = 'success';

      this.handleTaskCompletion<Result, Payload>(
        result,
        currentErrors,
        taskOptions.successHandlers,
        taskOptions.showNotification?.success,
        false,
      );

      return currentResult.value;
    } catch (error) {
      currentError.value = error;
      currentState.value = 'error';

      // Prepare error handlers with Laravel handler if enabled
      const errorHandlers = this.prepareErrorHandlers(taskOptions);

      this.handleTaskCompletion<Result, Payload>(
        error,
        currentErrors,
        errorHandlers,
        taskOptions.showNotification?.error,
        true,
      );

      return false;
    } finally {
      await taskOptions.always?.();

      if (shouldShowLoading === true) hideLoading();
    }
  }

  /**
   * Merges task options with custom options based on mutable properties.
   */
  private mergeTaskOptions<Result, Payload>(
    options: TaskOptions<Result, Payload>,
    mutableProps: (keyof TaskOptions<Result, Payload>)[],
    customOptions?: Partial<TaskOptions<Result, Payload>>,
  ): TaskOptions<Result, Payload> {
    if (mutableProps.length > 0 && customOptions) {
      const filteredOptions: Partial<TaskOptions<Result, Payload>> = {};

      for (const key of Object.keys(customOptions) as Array<keyof TaskOptions<Result, Payload>>) {
        if (mutableProps.includes(key)) {
          filteredOptions[key] = customOptions[key] as never;
        }
      }

      return { ...options, ...filteredOptions };
    }

    return customOptions ? { ...options, ...customOptions } : options;
  }

  /**
   * Prepares error handlers, prepending Laravel handler if enabled.
   */
  private prepareErrorHandlers<Result, Payload>(
    taskOptions: TaskOptions<Result, Payload>,
  ): ErrorCallbackOrValue | undefined {
    // If handleLaravelError is explicitly false, return original handlers
    if (taskOptions.handleLaravelError === false) {
      return taskOptions.errorHandlers;
    }

    // Extract Laravel handler options
    const laravelOptions =
      typeof taskOptions.handleLaravelError === 'object' ? taskOptions.handleLaravelError : {};
    const { userCallBack, translate } = laravelOptions;

    // Create Laravel handler with options
    const laravelHandler = this.errorHandlers.Laravel(userCallBack, translate) as ErrorHandler;

    // If no error handlers and handleLaravelError is not false, add Laravel handler
    if (!taskOptions.errorHandlers) {
      return [laravelHandler];
    }

    // If error handlers exist as array, prepend Laravel handler
    if (Array.isArray(taskOptions.errorHandlers)) {
      return [laravelHandler, ...taskOptions.errorHandlers];
    }

    // If error handler is a function, wrap it with Laravel handler first
    const errorHandler: ErrorHandler = {
      callback: (payload: unknown, context: ErrorHandlerContext) => {
        (taskOptions.errorHandlers as (error: unknown, context: ErrorHandlerContext) => unknown)(
          payload,
          context,
        );
      },
    };
    return [laravelHandler, errorHandler];
  }

  /**
   * Handles the completion of the task.
   */
  private handleTaskCompletion<Result = unknown, Payload = unknown>(
    data: unknown,
    currentErrors: Ref<ErrorBag>,
    handlers?: SuccessCallbackOrValue<Result, Payload> | ErrorCallbackOrValue,
    notification?: Resolvable<boolean | string>,
    isError: boolean = false,
  ): void {
    const errorContext: ErrorHandlerContext = {
      error: data,
      errors: currentErrors.value || new Map(),
      i18n: this.i18n,
    };

    const successContext: SuccessHandlerContext<Result> = { result: data as Result };

    if (Array.isArray(handlers)) {
      for (const handler of handlers) {
        const handlerKey = getSafe<Payload>(data, handler.key ?? '');

        if (handlerKey !== undefined && (!handler.matcher || handler.matcher(handlerKey))) {
          if (isError) {
            (handler as ErrorHandler).callback(data, errorContext);
          } else {
            (handler as SuccessHandler<Result, Payload>).callback(data as Payload, successContext);
          }
        }
      }
    } else {
      if (isError) {
        if (typeof handlers === 'function') {
          (handlers as (error: unknown, context: ErrorHandlerContext) => unknown)(
            data,
            errorContext,
          );
        }
      } else {
        (handlers as (data: Result) => unknown)?.(data as Result);
      }
    }

    void this.showResolvedNotification(notification, isError);
  }

  /**
   * Shows a notification based on the resolved notification value.
   */
  private async showResolvedNotification(
    notification: Resolvable<boolean | string> | undefined,
    isError: boolean,
  ): Promise<void> {
    const resolvedNotification = await resolveValue(notification);

    if (resolvedNotification === true) {
      showNotification(
        isError ? 'negative' : 'positive',
        isError ? this.i18n.t('common.task.error') : this.i18n.t('common.task.success'),
      );

      return;
    }

    if (typeof resolvedNotification === 'string') {
      showNotification(isError ? 'negative' : 'positive', resolvedNotification);

      return;
    }
  }
}
