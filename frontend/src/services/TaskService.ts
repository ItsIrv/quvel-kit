import type { ServiceContainer } from 'src/types/container.types';
import type { TaskOptions, TaskState } from 'src/types/task.types';
import { ref, type Ref } from 'vue';
import { showNotification } from 'src/utils/notifyUtil';
import { getSafe, resolveValue } from 'src/utils/objectUtils';
import { hideLoading, showLoading } from 'src/utils/loadingUtil';
import type { BootableService } from 'src/types/service.types';
import { Service } from './Service';

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
        // Execute the task
        const result = await taskOptions.task(
          // TODO: Look into this as Payload.
          (await resolveValue(taskOptions.taskPayload)) as Payload,
        );

        currentResult.value = result;
        currentState.value = 'success';

        // Execute success handlers
        if (taskOptions.successHandlers && Array.isArray(taskOptions.successHandlers)) {
          for (const successHandler of taskOptions.successHandlers) {
            const successHandlerKey = getSafe<Payload>(result, successHandler.key ?? '');

            if (successHandlerKey !== undefined) {
              if (successHandler.matcher && !successHandler.matcher(successHandlerKey)) {
                continue;
              }

              successHandler.callback(successHandlerKey, { result });
            }
          }
        } else {
          taskOptions.successHandlers?.(result);
        }

        // Show success toast
        const successToast = await resolveValue(taskOptions.showNotification?.success);
        if (successToast === true || typeof successToast === 'string') {
          showNotification(
            'positive',
            typeof successToast === 'string' ? successToast : container.i18n.t('task.success'),
          );
        }

        return currentResult.value;
      } catch (error) {
        currentError.value = error;
        currentState.value = 'error';

        let handledErrors = 0;

        // Execute error handlers
        if (taskOptions.errorHandlers && Array.isArray(taskOptions.errorHandlers)) {
          for (const errorHandler of taskOptions.errorHandlers) {
            const errorHandlerKey = getSafe(error, errorHandler.key ?? '');

            if (errorHandlerKey !== undefined) {
              if (errorHandler.matcher && !errorHandler.matcher(errorHandlerKey)) {
                continue;
              }

              errorHandler.callback(errorHandlerKey, { error, addError, errors: currentErrors });

              handledErrors++;
            }
          }
        } else {
          taskOptions.errorHandlers?.(error, { error, addError, errors: currentErrors });
        }

        // Fallback if no handlers caught the error
        if (!handledErrors && error instanceof Error) {
          addError('message', error.message);
        }

        // Show error toast
        const errorToast = await resolveValue(taskOptions.showNotification?.error);
        if (errorToast === true || typeof errorToast === 'string') {
          showNotification(
            'negative',
            typeof errorToast === 'string' ? errorToast : container.i18n.t('task.error'),
          );
        }

        return false;
      } finally {
        // Run finalization logic
        await taskOptions.always?.();

        // Hide loading indicator
        if (shouldShowLoading === true) {
          hideLoading();
        }
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
}
