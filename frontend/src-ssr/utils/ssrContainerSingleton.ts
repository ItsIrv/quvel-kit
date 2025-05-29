import type { SSRServiceContainer } from '../services/SSRServiceContainer';
import { createSSRContainer } from './createSSRContainer';

/**
 * Global singleton container for SSR
 * Ensures only one container instance across the entire SSR server
 */
let globalSSRContainer: SSRServiceContainer | null = null;

/**
 * Get or create the global SSR container
 */
export function getSSRContainer(): SSRServiceContainer {
  if (!globalSSRContainer) {
    globalSSRContainer = createSSRContainer();
  }
  return globalSSRContainer;
}

/**
 * Clear the global container (for testing or shutdown)
 */
export function clearSSRContainer(): void {
  globalSSRContainer = null;
}