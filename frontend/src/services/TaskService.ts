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
} from 'src/types/task.types';
import { computed, ComputedRef, ref, type Ref } from 'vue';
import { showNotification } from 'src/utils/notifyUtil';
import { getSafe, resolveValue } from 'src/utils/objectUtils';
import { hideLoading, showLoading } from 'src/utils/loadingUtil';
import type { BootableService } from 'src/types/service.types';
import { Service } from './Service';
import type { ServiceContainer } from './ServiceContainer';
import { ErrorBag } from 'src/types/error.types';
import { LaravelErrorHandler } from 'src/utils/errorUtil';

/**
 * Task Service - Manages async operations with built-in error handling, notifications, and loading.
 */
export class TaskService extends Service implements BootableService {
  /** Reference the whole container to provide helpers to the handlers */
  private container: ServiceContainer | null = null;

  /**
   * Common error handlers.
   */
  readonly errorHandlers = Object.freeze({
    Laravel: LaravelErrorHandler,
  });

  /**
   * Injects the service container dependencies.
   */
  register(container: ServiceContainer): void {
    this.container = container;
  }

  /**
   * Creates a new managed task.
   */
  newTask<Result = unknown, Payload = unknown>(
    options: TaskOptions<Result, Payload>,
  ): {
    isActive: ComputedRef<boolean>;
    run: typeof runTask;
    reset: typeof resetTask;
    state: Ref<TaskState>;
    error: Ref<unknown>;
    errors: Ref<ErrorBag>;
    result: Ref<Result | undefined>;
  } {
    const currentError = ref<unknown>();
    const currentErrors = ref<ErrorBag>(new Map());
    const currentResult = ref<Result>();
    const currentState = ref<TaskState>('fresh');
    const container = this.container as ServiceContainer;

    /**
     * Resets the task state.
     */
    function resetTask(): void {
      currentError.value = undefined;
      currentErrors.value = new Map();
      currentResult.value = undefined;
      currentState.value = 'fresh';
    }

    /**
     * Runs the task with optional overrides.
     */
    async function runTask(
      customOptions?: Partial<TaskOptions<Result, Payload>>,
    ): Promise<Result | false> {
      resetTask();

      currentState.value = 'active';

      // Merge provided options with initial options
      const taskOptions: TaskOptions<Result, Payload> = { ...options, ...customOptions };

      // Check if the task should run
      if ((await resolveValue(taskOptions.shouldRun)) === false) {
        return false;
      }

      // Show loading indicator if applicable
      const shouldShowLoading = await resolveValue(taskOptions.showLoading);
      if (shouldShowLoading === true) {
        showLoading(0, taskOptions.loadingOptions);
      }

      try {
        const result = await taskOptions.task(
          (await resolveValue(taskOptions.taskPayload)) as Payload,
        );

        currentResult.value = result;
        currentState.value = 'success';

        handleTaskCompletion(
          result,
          taskOptions.successHandlers,
          taskOptions.showNotification?.success,
        );

        return currentResult.value;
      } catch (error) {
        currentError.value = error;
        currentState.value = 'error';

        handleTaskCompletion(
          error,
          taskOptions.errorHandlers,
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
     * Handles success or error processing, including executing handlers and showing notifications.
     */
    function handleTaskCompletion<Payload>(
      data: unknown,
      handlers?: SuccessCallbackOrValue<Payload> | ErrorCallbackOrValue<unknown>,
      notification?: Resolvable<boolean | string>,
      isError = false,
    ): void {
      let handledCalls = 0;

      const t = container.i18n.instance.global.t?.bind(container);
      const te = container.i18n.instance.global.te?.bind(container);
      const errorContext: ErrorHandlerContext<unknown> = {
        error: data,
        errors: currentErrors.value || {},
        i18n: {
          t,
          te,
        },
      };

      const context: SuccessHandlerContext<Payload> = { result: data as Payload };

      if (Array.isArray(handlers)) {
        for (const handler of handlers) {
          const handlerKey = getSafe<Payload>(data, handler.key ?? '');

          if (handlerKey !== undefined && (!handler.matcher || handler.matcher(handlerKey))) {
            if (isError) {
              (handler as unknown as ErrorHandler).callback(handlerKey, errorContext);
            } else {
              (handler as unknown as SuccessHandler).callback(handlerKey, context);
            }

            handledCalls++;
          }
        }
      } else {
        if (isError) {
          handlers?.(data, errorContext);
        } else {
          (handlers as (data: Result, context: SuccessHandlerContext<Payload>) => unknown)?.(
            data as Result,
            context,
          );
        }
      }

      if (isError && handledCalls === 0 && data instanceof Error) {
        // addError('message', data.message);
      }

      void showResolvedNotification(notification, isError);
    }

    /**
     * Shows notification if configured.
     */
    async function showResolvedNotification(
      notification: Resolvable<boolean | string> | undefined,
      isError: boolean,
    ): Promise<void> {
      const resolvedNotification = await resolveValue(notification);

      if (resolvedNotification === true || typeof resolvedNotification === 'string') {
        if (typeof resolvedNotification === 'string') {
          showNotification(isError ? 'negative' : 'positive', resolvedNotification);
        } else {
          let responseMessage: string;

          // Try to get messages from result or errors
          if (isError && currentErrors.value.has('message')) {
            responseMessage =
              currentErrors.value.get('message') || container.i18n.t('common.task.error');
          } else {
            // On success try to get message from result
            responseMessage = (currentResult.value as { message: string }).message;
          }

          showNotification(isError ? 'negative' : 'positive', container.i18n.t(responseMessage));
        }
      }
    }

    const isActive = computed(() => currentState.value === 'active');

    return {
      isActive,
      run: runTask,
      reset: resetTask,
      state: currentState,
      error: currentError,
      errors: currentErrors,
      result: currentResult,
    };
  }

  /**
   * Creates a new frozen task with a whitelist of allowed property overrides in run().
   * This is useful for tasks that should not be changed, such as global helper tasks.
   *
   * @param options The options for the task.
   * @param mutableProps The properties that can be overridden.
   * @returns The frozen task.
   */
  newFrozenTask<Result = unknown, Payload = unknown>(
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
    const task = this.newTask<Result, Payload>(options);

    /**
     * Runs the frozen task while ensuring only allowed properties are overridden.
     */
    async function run(
      customOptions?: Partial<TaskOptions<Result, Payload>>,
    ): Promise<Result | false> {
      if (!customOptions) {
        return await task.run();
      }

      // Create a filtered options object with only allowed keys
      const filteredOptions: Partial<TaskOptions<Result, Payload>> = {};

      for (const key of Object.keys(customOptions) as Array<keyof TaskOptions<Result, Payload>>) {
        if (mutableProps.includes(key)) {
          filteredOptions[key] = customOptions[key] as never;
        }
      }

      return await task.run(filteredOptions);
    }

    function reset(): void {
      task.reset();
    }

    return {
      run,
      reset,
      isActive: task.isActive,
      state: task.state,
      error: task.error,
      errors: task.errors,
      result: task.result,
    };
  }

  withLoading<Result = unknown, Payload = unknown>(options: TaskOptions<Result, Payload>) {
    return this.newTask<Result, Payload>({
      ...options,
      showLoading: true,
    });
  }
}
