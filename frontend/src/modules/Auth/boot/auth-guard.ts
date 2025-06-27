import { defineBoot } from '#q-app/wrappers';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { QuvelRoutes } from 'src/modules/Quvel/router/constants';
import { RouteMeta } from 'vue-router';

/**
 * Authentication Guard Boot File
 *
 * Handles authentication checks and redirects for both SSR and client-side navigation.
 * Protects routes based on meta.requiresAuth and meta.skipAuth properties.
 */
export default defineBoot(async ({ router, store, urlPath, redirect }) => {
  if (router.resolve(urlPath)?.meta.skipAuth === true) {
    return;
  }

  // Initialize session store
  const sessionStore = useSessionStore(store);

  // Only fetch session if not already initialized (prevents double loading)
  if (!sessionStore.isInitialized) {
    try {
      await sessionStore.fetchSession();
    } catch {
      // Ignore fetch errors - user is just not authenticated
    }
  }

  // Get configuration from environment variables
  const requireAuthByDefault = import.meta.env.VITE_REQUIRE_AUTH_BY_DEFAULT !== 'false';
  const loginRoute = import.meta.env.VITE_AUTH_LOGIN_ROUTE || QuvelRoutes.LANDING;

  /**
   * Centralized auth check logic to avoid duplication
   * Security-first approach: auth is required unless explicitly disabled
   */
  const performAuthCheck = (path: string, routeMeta?: RouteMeta) => {
    if (routeMeta?.skipAuth === true) {
      return { action: 'continue' };
    }

    // Check if route requires auth (default behavior based on env var)
    const routeRequiresAuth =
      routeMeta?.requiresAuth !== undefined ? routeMeta.requiresAuth : requireAuthByDefault;

    // For protected routes, check if user is authenticated
    if (routeRequiresAuth && !sessionStore.isAuthenticated) {
      return { action: 'redirect', route: loginRoute };
    }

    return { action: 'continue' };
  };

  // Initial SSR auth check
  const initialCheck = performAuthCheck(urlPath, router.resolve(urlPath)?.meta);
  if (initialCheck.action === 'redirect') {
    redirect({ name: initialCheck.route });
    return;
  }

  // Router guard for client-side navigation
  router.beforeEach(async (to, from, next) => {
    // Only fetch session if not initialized and we're on client
    if (!sessionStore.isInitialized) {
      try {
        await sessionStore.fetchSession();
      } catch {
        // Ignore fetch errors - user is just not authenticated
      }
    }

    // Perform auth check with centralized logic
    const authCheck = performAuthCheck(to.path, to.meta);
    if (authCheck.action === 'redirect') {
      next({ name: authCheck.route });
      return;
    }

    next();
  });
});
