import type { AxiosInstance } from 'axios';

/**
 * Defines the structure of the Dependency Injection (DI) container.
 */
export interface ServiceContainer {
  api: AxiosInstance;
  // Future services can be added here:
  // authService: AuthService;
  // logService: LogService;
}
