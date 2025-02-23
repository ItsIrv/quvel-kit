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
import { ref, type Ref } from 'vue';
import { showNotification } from 'src/utils/notifyUtil';
import { getSafe, resolveValue } from 'src/utils/objectUtils';
import { hideLoading, showLoading } from 'src/utils/loadingUtil';
import type { BootableService } from 'src/types/service.types';
import { Service } from './Service';
import type { ServiceContainer } from './ServiceContainer';

/**
 * Task Service - Manages async operations with built-in error handling, notifications, and loading.
 */
export class TaskService extends Service implements BootableService {
  private container: ServiceContainer | null = null;

  /**
   * Injects the service container dependencies.
   */
  boot(container: ServiceContainer): void {
    this.container = container;
  }

  /**
   * Creates a new managed task.
   */
  newTask<Result = unknown, Payload = unknown>(
    options: TaskOptions<Result, Payload>,
  ): {
    run: typeof runTask;
    reset: typeof resetTask;
    state: Ref<TaskState>;
    error: Ref<unknown>;
    errors: Ref<Record<string, unknown>>;
    result: Ref<Result | undefined>;
  } {
    const currentError = ref<unknown>();
    const currentErrors = ref<Record<string, unknown>>({});
    const currentResult = ref<Result>();
    const currentState = ref<TaskState>('fresh');
    const container = this.container as ServiceContainer;

    /**
     * Resets the task state.
     */
    function resetTask(): void {
      currentError.value = undefined;
      currentErrors.value = {};
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

      const errorContext: ErrorHandlerContext<unknown> = {
        error: data,
        addError,
        errors: currentErrors,
      };
      const context: SuccessHandlerContext<Payload> = { result: data as Payload };

      if (Array.isArray(handlers)) {
        for (const handler of handlers) {
          const handlerKey = getSafe<Payload>(data, handler.key ?? '');

          if (handlerKey !== undefined && (!handler.matcher || handler.matcher(handlerKey))) {
            if (isError) {
              (handler.callback as unknown as ErrorHandler).callback(handlerKey, errorContext);
            } else {
              (handler.callback as unknown as SuccessHandler).callback(handlerKey, context);
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
        addError('message', data.message);
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

      if (resolvedNotification !== undefined && resolvedNotification !== false) {
        showNotification(
          isError ? 'negative' : 'positive',
          typeof resolvedNotification === 'string'
            ? resolvedNotification
            : container?.i18n.t(isError ? 'task.error' : 'task.success'),
        );
      }
    }

    /**
     * Adds an error to the current error state.
     */
    function addError(key: string, value: unknown): void {
      currentErrors.value = { ...currentErrors.value, [key]: value };
    }

    return {
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
    run: (customOptions?: Partial<TaskOptions<Result, Payload>>) => Promise<Result | false>;
    reset: () => void;
    state: Ref<TaskState>;
    error: Ref<unknown>;
    errors: Ref<Record<string, unknown>>;
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
      state: task.state,
      error: task.error,
      errors: task.errors,
      result: task.result,
    };
  }
}
