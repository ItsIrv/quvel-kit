import type { AxiosError } from 'axios';
import type { ErrorHandler, ErrorHandlerContext } from 'src/modules/Core/types/task.types';

import { LaravelErrorResponse } from 'src/modules/Core/types/laravel.types';

/**
 * Handles Laravel errors by extracting `message` and top-level `errors` into the ErrorBag.
 * @param userCallBack - Optional callback function for additional error handling.
 * @param translate - Whether to translate error messages.
 */
export function LaravelErrorHandler(
  userCallBack?: (err: AxiosError, context: ErrorHandlerContext<LaravelErrorResponse>) => void,
  translate: boolean = false,
): ErrorHandler<boolean, LaravelErrorResponse> {
  return {
    key: 'isAxiosError',
    matcher: (isAxiosError: boolean) => isAxiosError,
    callback: (_: boolean, context: ErrorHandlerContext<LaravelErrorResponse>): void => {
      const responseData = context.error.response?.data;
      const { errors } = responseData || {};

      // Store `message` if it's not already in errors
      if (typeof responseData?.message === 'string') {
        const translatedMessage =
          translate && context.i18n.te(responseData.message)
            ? context.i18n.t(responseData.message)
            : responseData.message;

        const isDuplicate = errors
          ? Object.values(errors)
              .flat()
              .some((msg) => msg.includes(responseData.message ?? ''))
          : false;

        if (!isDuplicate) {
          context.errors.set('message', translatedMessage);
        }
      }

      // Store only top-level error keys
      if (errors) {
        for (const [key, value] of Object.entries(errors)) {
          if (typeof value === 'string') {
            context.errors.set(
              key,
              translate && context.i18n.te(value) ? context.i18n.t(value) : value,
            );
          } else if (Array.isArray(value) && value[0] && typeof value[0] === 'string') {
            context.errors.set(
              key,
              translate && context.i18n.te(value[0]) ? context.i18n.t(value[0]) : value[0],
            );
          }
        }
      }

      // Execute optional callback
      userCallBack?.(context.error, context);
    },
  };
}
