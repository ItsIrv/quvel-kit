import { InstallationService } from './InstallationService';
import { AuthService } from './AuthService';

/**
 * Service provider for dependency injection
 * Provides singleton instances of services used throughout the TenantAdmin module
 */
export class ServiceProvider {
  private static instance: ServiceProvider;
  private services: Map<string, any> = new Map();

  private constructor() {
    this.registerServices();
  }

  /**
   * Get the singleton instance of ServiceProvider
   */
  static getInstance(): ServiceProvider {
    if (!ServiceProvider.instance) {
      ServiceProvider.instance = new ServiceProvider();
    }
    return ServiceProvider.instance;
  }

  /**
   * Register all services
   */
  private registerServices(): void {
    // Register InstallationService as a singleton
    this.services.set('installation', new InstallationService());
    
    // Register AuthService as a singleton
    this.services.set('auth', new AuthService());
  }

  /**
   * Get a service by key
   */
  get<T>(key: string): T {
    const service = this.services.get(key);
    if (!service) {
      throw new Error(`Service '${key}' not found in ServiceProvider`);
    }
    return service as T;
  }

  /**
   * Get the InstallationService instance
   */
  get installation(): InstallationService {
    return this.get<InstallationService>('installation');
  }

  /**
   * Get the AuthService instance
   */
  get auth(): AuthService {
    return this.get<AuthService>('auth');
  }

  /**
   * Register a custom service
   */
  register(key: string, service: any): void {
    this.services.set(key, service);
  }

  /**
   * Check if a service is registered
   */
  has(key: string): boolean {
    return this.services.has(key);
  }

  /**
   * Clear all services (useful for testing)
   */
  clear(): void {
    this.services.clear();
  }
}

// Export a singleton instance
export const serviceProvider = ServiceProvider.getInstance();