import { Loading, type QLoadingShowOptions } from 'quasar';

/**
 * Shows the loading spinner with a customizable timeout.
 * @param timeout - Timeout in milliseconds before hiding the spinner automatically (default: 1000ms).
 * @param options - Additional options for customizing the loading spinner.
 */
export function showLoading(timeout = 0, options?: QLoadingShowOptions): void {
  if (typeof window === 'undefined') return;

  Loading.show({
    ...options,
  });

  if (timeout > 0) {
    setTimeout(hideLoading, timeout);
  }
}

/**
 * Hides the loading spinner.
 */
export function hideLoading(): void {
  if (typeof window === 'undefined') return;

  Loading.hide();
}

/**
 * Executes a given function with loading spinner displayed during its execution.
 * @param task - The asynchronous task to execute.
 * @param options - Options for customizing the loading spinner.
 */
export async function withLoading<T>(
  task: () => Promise<T>,
  options?: { message?: string; timeout?: number },
): Promise<T> {
  try {
    showLoading(options?.timeout, { message: options?.message ?? '' });

    return await task();
  } finally {
    hideLoading();
  }
}
