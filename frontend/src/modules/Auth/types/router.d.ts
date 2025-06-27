/**
 * Auth Module Route Meta Type Extensions
 *
 * Extends Vue Router's RouteMeta interface to include authentication configuration.
 */

import type { AuthMeta } from './auth-meta';

declare module 'vue-router' {
  interface RouteMeta {
    /**
     * Authentication configuration for this route.
     * 
     * Use the helper functions from 'src/modules/Auth/utils/auth-meta' for type safety:
     * - createGuestOnlyAuth() - for login/signup pages
     * - createProtectedAuth() - explicitly require authentication  
     * - createPublicAuth() - explicitly allow public access
     * - createSkipAuth() - skip all auth logic
     * - createAuthMeta(options) - custom configuration
     * 
     * @example
     * ```typescript
     * // Guest-only route (login page)
     * meta: {
     *   auth: createGuestOnlyAuth()
     * }
     * 
     * // Protected route
     * meta: {
     *   auth: createProtectedAuth()
     * }
     * 
     * // Public route
     * meta: {
     *   auth: createPublicAuth()
     * }
     * 
     * // Custom configuration
     * meta: {
     *   auth: createAuthMeta({
     *     guestOnly: true,
     *     redirectTo: '/custom-dashboard'
     *   })
     * }
     * ```
     */
    auth?: AuthMeta;
  }
}

export {};