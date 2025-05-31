import { serviceProvider } from '../services/ServiceProvider';
import type { InstallationService } from '../services/InstallationService';
import type { AuthService } from '../services/AuthService';

/**
 * Composable for accessing services within Vue components
 * Provides type-safe access to all registered services
 */
export function useServices() {
  return {
    installation: serviceProvider.installation,
    auth: serviceProvider.auth,
    // Add more services here as they are created
  };
}

/**
 * Composable specifically for the InstallationService
 */
export function useInstallationService(): InstallationService {
  return serviceProvider.installation;
}

/**
 * Composable specifically for the AuthService
 */
export function useAuthService(): AuthService {
  return serviceProvider.auth;
}