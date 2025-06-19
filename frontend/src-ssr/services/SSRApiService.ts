import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRSingletonService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import { AxiosInstance, AxiosRequestConfig } from 'axios';
import { createAxios } from '../utils/createAxios';

/**
 * SSR-specific API service (Singleton)
 * Provides HTTP client without browser dependencies
 * This is a stateless singleton service - request context is passed as method parameters
 */
export class SSRApiService extends SSRService implements SSRSingletonService {
  private api!: AxiosInstance;
  private logger!: SSRLogService;

  override register(container: SSRServiceContainer): void {
    this.logger = container.get(SSRLogService);

    // Setup interceptors after logger is available
    this.setupInterceptors();
  }

  constructor() {
    super();
    // Create axios instance with SSR configuration
    this.api = createAxios({
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
    });
  }

  private setupInterceptors(): void {
    // Add request interceptor
    this.api.interceptors.request.use(
      (config) => {
        this.logger.debug('SSR API Request', {
          method: config.method?.toUpperCase(),
          url: config.url,
        });

        return config;
      },
      (error) => {
        this.logger.error('SSR API Request Error', { error: error.message });
        return Promise.reject(error as Error);
      },
    );

    // Add response interceptor
    this.api.interceptors.response.use(
      (response) => {
        this.logger.debug('SSR API Response', {
          method: response.config.method?.toUpperCase(),
          url: response.config.url,
          status: response.status,
          statusText: response.statusText,
        });
        return response;
      },
      (error) => {
        this.logger.error('SSR API Response Error', {
          method: error.config?.method?.toUpperCase(),
          url: error.config?.url,
          status: error.response?.status,
          message: error.message,
        });
        return Promise.reject(error as Error);
      },
    );
  }
  /**
   * Get request
   */
  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.get<T>(url, config);
    return response.data;
  }

  /**
   * Post request
   */
  async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.post<T>(url, data, config);
    return response.data;
  }

  /**
   * Put request
   */
  async put<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.put<T>(url, data, config);
    return response.data;
  }

  /**
   * Delete request
   */
  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.delete<T>(url, config);
    return response.data;
  }

  /**
   * Get the axios instance for advanced usage
   */
  get instance(): AxiosInstance {
    return this.api;
  }
}
