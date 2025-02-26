import type { AxiosError } from 'axios';
import type { ErrorHandler, ErrorHandlerContext } from 'src/types/task.types';

/**
 * Defines the Laravel error response structure.
 */
type LaravelErrorResponse = AxiosError<{ message?: string; errors?: Record<string, unknown> }>;

/**
 * Handles Laravel errors by extracting `message` and `errors` from response data.
 *
 * @param callback - Optional callback for additional handling.
 * @param translate - Whether to translate error messages.
 */
export function LaravelErrorHandler(
  userCallBack?: (err: AxiosError, context: ErrorHandlerContext<LaravelErrorResponse>) => void,
): ErrorHandler<boolean, LaravelErrorResponse> {
  return {
    key: 'isAxiosError',
    matcher: (isAxiosError: boolean) => isAxiosError === true,
    callback: (_: boolean, context: ErrorHandlerContext<LaravelErrorResponse>): void => {
      const responseData = context.error.response?.data;

      // Handle `message` key
      if (responseData?.message !== undefined) {
        if (context.i18n.te(responseData.message)) {
          context.addError('message', context.i18n.t(responseData.message));
        } else {
          context.addError('message', responseData.message);
        }
      }

      // Handle `errors` object
      if (responseData?.errors) {
        Object.entries(responseData.errors).forEach(([key, messages]) => {
          if (Array.isArray(messages)) {
            console.log(key, messages);
            if (context.i18n.te(`${key}.${messages[0]}`)) {
              context.addError(key, context.i18n.t(`${key}.${messages[0]}`));
            } else {
              context.addError(key, messages[0]);
            }
          }
        });
      }

      // Execute optional callback
      userCallBack?.(context.error, context);
    },
  };
}
