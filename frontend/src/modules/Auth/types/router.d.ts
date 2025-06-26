/**
 * Auth Module Route Meta Type Extensions
 *
 * Extends Vue Router's RouteMeta interface to include authentication-specific properties.
 */

declare module 'vue-router' {
  interface RouteMeta {
    /**
     * Explicitly require authentication for this route.
     * If undefined, falls back to VITE_REQUIRE_AUTH_BY_DEFAULT environment variable.
     */
    requiresAuth?: boolean;

    /**
     * Skip all authentication checks for this route.
     * Useful for static pages that don't need authentication.
     */
    skipAuth?: boolean;
  }
}

export {};
