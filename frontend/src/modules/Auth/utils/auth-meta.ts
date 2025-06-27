/**
 * Authentication Meta Utilities
 * 
 * Helper functions for creating type-safe auth meta configurations.
 */

import type { AuthMeta } from '../types/auth-meta';

/**
 * Generic factory function for creating type-safe auth meta objects
 * 
 * @param options - Auth configuration options
 * @returns AuthMeta object for use in route meta
 * 
 * @example
 * ```typescript
 * meta: {
 *   auth: createAuthMeta({
 *     guestOnly: true,
 *     redirectTo: '/dashboard'
 *   })
 * }
 * ```
 */
export function createAuthMeta(options: AuthMeta): AuthMeta {
  return { ...options };
}

/**
 * Create auth meta for guest-only routes (login, signup, etc.)
 * Redirects authenticated users to the specified route or default success route.
 * 
 * @param redirectTo - Optional custom redirect target for authenticated users
 * @returns AuthMeta object configured for guest-only access
 * 
 * @example
 * ```typescript
 * // Redirect to default success route
 * meta: { auth: createGuestOnlyAuth() }
 * 
 * // Redirect to custom route
 * meta: { auth: createGuestOnlyAuth('/custom-dashboard') }
 * ```
 */
export function createGuestOnlyAuth(redirectTo?: string): AuthMeta {
  return {
    guestOnly: true,
    requiresAuth: false, // Explicitly allow unauthenticated access
    ...(redirectTo && { redirectTo }),
  };
}

/**
 * Create auth meta for protected routes that explicitly require authentication
 * 
 * @returns AuthMeta object configured to require authentication
 * 
 * @example
 * ```typescript
 * meta: { auth: createProtectedAuth() }
 * ```
 */
export function createProtectedAuth(): AuthMeta {
  return {
    requiresAuth: true,
  };
}

/**
 * Create auth meta for public routes that explicitly allow unauthenticated access
 * 
 * @returns AuthMeta object configured for public access
 * 
 * @example
 * ```typescript
 * meta: { auth: createPublicAuth() }
 * ```
 */
export function createPublicAuth(): AuthMeta {
  return {
    requiresAuth: false,
  };
}

/**
 * Create auth meta that skips all authentication logic
 * Useful for static pages, health checks, or routes with custom auth handling
 * 
 * @returns AuthMeta object configured to skip all auth checks
 * 
 * @example
 * ```typescript
 * meta: { auth: createSkipAuth() }
 * ```
 */
export function createSkipAuth(): AuthMeta {
  return {
    skipAuth: true,
  };
}