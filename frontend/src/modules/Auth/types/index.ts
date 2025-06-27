/**
 * Auth Module Type Exports
 * 
 * Centralized exports for all auth-related types and utilities.
 */

// Auth meta types
export type { AuthMeta } from './auth-meta';

// Auth meta helper functions
export {
  createAuthMeta,
  createGuestOnlyAuth,
  createProtectedAuth,
  createPublicAuth,
  createSkipAuth,
} from '../utils/auth-meta';

// Router type extensions are automatically available via module declaration