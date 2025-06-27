import { defineBoot } from '#q-app/wrappers';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { QuvelRoutes } from 'src/modules/Quvel/router/constants';
import { DashboardRoutes } from 'src/modules/Dashboard/router/constants';
import type { RouteMeta } from 'vue-router';
import type { AuthMeta } from 'src/modules/Auth/types/auth-meta';

/**
 * Authentication Guard Boot File
 *
 * Handles authentication checks and redirects for both SSR and client-side navigation.
 * Protects routes based on meta.auth configuration object.
 * 
 * Supports:
 * - Protected routes (require authentication)
 * - Public routes (allow unauthenticated access) 
 * - Guest-only routes (redirect authenticated users away)
 * - Skip auth routes (bypass all auth logic)
 */
export default defineBoot(async ({ router, store, urlPath, redirect }) => {
  // Early check for skip auth
  const resolvedRoute = router.resolve(urlPath);
  if (resolvedRoute?.meta.auth?.skipAuth === true) {
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
  const successRoute = import.meta.env.VITE_AUTH_SUCCESS_ROUTE || DashboardRoutes.DASHBOARD;

  /**
   * Centralized auth check logic
   * Security-first approach: auth is required unless explicitly disabled
   */
  const performAuthCheck = (routeMeta?: RouteMeta) => {
    const authConfig: AuthMeta = routeMeta?.auth || {};

    // Skip auth entirely if configured
    if (authConfig.skipAuth === true) {
      return { action: 'continue' };
    }

    // Handle guest-only routes
    if (authConfig.guestOnly === true) {
      if (sessionStore.isAuthenticated) {
        // Redirect authenticated users away from guest-only pages
        const redirectTarget = authConfig.redirectTo || successRoute;
        return { action: 'redirect', route: redirectTarget };
      }
      // Allow unauthenticated users to continue to guest-only pages
      return { action: 'continue' };
    }

    // Determine if this route requires authentication
    const routeRequiresAuth = authConfig.requiresAuth !== undefined 
      ? authConfig.requiresAuth 
      : requireAuthByDefault;

    // Check authentication requirement
    if (routeRequiresAuth && !sessionStore.isAuthenticated) {
      return { action: 'redirect', route: loginRoute };
    }

    return { action: 'continue' };
  };

  /**
   * Ensure session is initialized before auth checks
   */
  const ensureSessionInitialized = async () => {
    if (!sessionStore.isInitialized) {
      try {
        await sessionStore.fetchSession();
      } catch {
        // Ignore fetch errors - user is just not authenticated
      }
    }
  };

  // Ensure session is loaded for initial SSR check
  await ensureSessionInitialized();

  // Initial SSR auth check
  const initialCheck = performAuthCheck(resolvedRoute?.meta);
  if (initialCheck.action === 'redirect') {
    redirect({ name: initialCheck.route });
    return;
  }

  // Router guard for client-side navigation
  router.beforeEach(async (to, from, next) => {
    // Ensure session is initialized
    await ensureSessionInitialized();

    // Perform auth check with centralized logic
    const authCheck = performAuthCheck(to.meta);
    if (authCheck.action === 'redirect') {
      next({ name: authCheck.route });
      return;
    }

    next();
  });
});