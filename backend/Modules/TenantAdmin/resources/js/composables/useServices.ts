import { serviceProvider } from '../services/ServiceProvider';
import type { InstallationService } from '../services/InstallationService';
import type { AuthService } from '../services/AuthService';
import type { TenantService } from '../services/TenantService';

/**
 * Composable for accessing services within Vue components
 * Provides type-safe access to all registered services
 */
export function useServices() {
  return {
    installation: serviceProvider.installation,
    auth: serviceProvider.auth,
    tenant: serviceProvider.tenant,
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

/**
 * Composable specifically for the TenantService
 */
export function useTenantService(): TenantService {
  return serviceProvider.tenant;
}