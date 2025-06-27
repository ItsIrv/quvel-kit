/**
 * Authentication Meta Types
 * 
 * Type definitions for route-level authentication configuration.
 */

/**
 * Authentication configuration for routes
 */
export interface AuthMeta {
  /**
   * Explicitly require authentication for this route.
   * If undefined, falls back to VITE_REQUIRE_AUTH_BY_DEFAULT environment variable.
   * 
   * @example
   * ```typescript
   * auth: { requiresAuth: true }
   * ```
   */
  requiresAuth?: boolean;

  /**
   * Skip all authentication checks for this route.
   * Useful for static pages, public APIs, or routes that handle auth manually.
   * Takes precedence over all other auth settings.
   * 
   * @example
   * ```typescript
   * auth: { skipAuth: true }
   * ```
   */
  skipAuth?: boolean;

  /**
   * Redirect authenticated users away from this route.
   * Perfect for login, signup, and other auth-related pages where
   * authenticated users shouldn't have access.
   * 
   * @example
   * ```typescript
   * auth: { guestOnly: true }
   * ```
   */
  guestOnly?: boolean;

  /**
   * Custom redirect target for authenticated users on guest-only routes.
   * If not specified, uses VITE_AUTH_SUCCESS_ROUTE environment variable
   * or falls back to dashboard.
   * 
   * @example
   * ```typescript
   * auth: { guestOnly: true, redirectTo: '/custom-dashboard' }
   * ```
   */
  redirectTo?: string;
}