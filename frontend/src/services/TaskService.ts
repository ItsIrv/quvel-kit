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
    const container = this.container as ServiceContainer;

    function resetTask(): void {
      currentError.value = undefined;
      currentErrors.value = new Map();
      currentResult.value = undefined;
      currentState.value = 'fresh';
    }

    async function runTask(
      customOptions?: Partial<TaskOptions<Result, Payload>>,
    ): Promise<Result | false> {
      resetTask();
      currentState.value = 'active';

      // Enforce frozen behavior if mutableProps are defined
      let taskOptions: TaskOptions<Result, Payload> = options;

      if (mutableProps.length > 0 && customOptions) {
        const filteredOptions: Partial<TaskOptions<Result, Payload>> = {};

        for (const key of Object.keys(customOptions) as Array<keyof TaskOptions<Result, Payload>>) {
          if (mutableProps.includes(key)) {
            filteredOptions[key] = customOptions[key] as never;
          }
        }

        taskOptions = { ...options, ...filteredOptions };
      } else if (customOptions) {
        taskOptions = { ...options, ...customOptions };
      }

      if ((await resolveValue(taskOptions.shouldRun)) === false) {
        return false;
      }

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

    function handleTaskCompletion<Payload>(
      data: unknown,
      handlers?: SuccessCallbackOrValue<Payload> | ErrorCallbackOrValue,
      notification?: Resolvable<boolean | string>,
      isError = false,
    ): void {
      const t = container.i18n.instance.global.t?.bind(container);
      const te = container.i18n.instance.global.te?.bind(container);
      const errorContext: ErrorHandlerContext = {
        error: data,
        errors: currentErrors.value || {},
        i18n: {
          t,
          te,
        },
      };

      const successContext: SuccessHandlerContext<Payload> = { result: data as Payload };

      if (Array.isArray(handlers)) {
        for (const handler of handlers) {
          const handlerKey = getSafe<Payload>(data, handler.key ?? '');

          if (handlerKey !== undefined && (!handler.matcher || handler.matcher(handlerKey))) {
            if (isError) {
              (handler as unknown as ErrorHandler).callback(handlerKey, errorContext);
            } else {
              (handler as unknown as SuccessHandler).callback(handlerKey, successContext);
            }
          }
        }
      } else {
        if (isError) {
          handlers?.(data as Payload, errorContext);
        } else {
          (handlers as (data: Result) => unknown)?.(data as Result);
        }
      }

      void showResolvedNotification(notification, isError);
    }

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

          if (isError && currentErrors.value.has('message')) {
            responseMessage =
              currentErrors.value.get('message') || container.i18n.t('common.task.error');
          } else {
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
}
