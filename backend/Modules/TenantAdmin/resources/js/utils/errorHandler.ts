import type { ApiError } from '../services/BaseApiService';

/**
 * Utility functions for handling API errors in a consistent way
 */

/**
 * Check if an error is a validation error (422 status)
 */
export function isValidationError(error: ApiError): boolean {
  return error.status === 422 && !!error.errors;
}

/**
 * Check if an error is an authentication error (401 status)
 */
export function isAuthenticationError(error: ApiError): boolean {
  return error.status === 401;
}

/**
 * Check if an error is a server error (5xx status)
 */
export function isServerError(error: ApiError): boolean {
  return (error.status ?? 0) >= 500;
}

/**
 * Get a user-friendly error message from an API error
 */
export function getUserFriendlyMessage(error: ApiError): string {
  if (isValidationError(error)) {
    return 'Please fix the errors in the form and try again.';
  }
  
  if (isAuthenticationError(error)) {
    return 'You are not authenticated. Please log in and try again.';
  }
  
  if (isServerError(error)) {
    return 'A server error occurred. Please try again later.';
  }
  
  if (error.status === 0) {
    return 'Network error. Please check your connection and try again.';
  }
  
  return error.message || 'An unexpected error occurred.';
}

/**
 * Get validation errors in a format suitable for displaying in forms
 */
export function getValidationErrors(error: ApiError): Record<string, string[]> {
  if (isValidationError(error) && error.errors) {
    return error.errors;
  }
  return {};
}

/**
 * Log error for debugging purposes while hiding sensitive information
 */
export function logError(error: ApiError, context?: string): void {
  const logData = {
    message: error.message,
    status: error.status,
    context,
    timestamp: new Date().toISOString(),
  };
  
  console.error('[TenantAdmin Error]', logData);
}