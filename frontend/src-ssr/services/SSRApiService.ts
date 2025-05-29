import { SSRService } from './SSRService';
import type { SSRServiceContainer } from './SSRServiceContainer';
import type { SSRRegisterService, SSRServiceOptions, SSRSsrAwareService } from '../types/service.types';
import { SSRLogService } from './SSRLogService';
import type { Request, Response } from 'express';
import { AxiosInstance, AxiosRequestConfig } from 'axios';
import { createAxios } from '../utils/createAxios';

/**
 * SSR-specific API service
 * Provides HTTP client without browser dependencies
 */
export class SSRApiService extends SSRService implements SSRRegisterService, SSRSsrAwareService {
  private api!: AxiosInstance;
  private logger!: SSRLogService;
  private req: Request | undefined;
  private res: Response | undefined;

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

  override boot(options?: SSRServiceOptions): void {
    if (options) {
      this.req = options.req;
      this.res = options.res;
      
      // Update base URL if tenant config is available
      if (options.req?.tenantConfig?.internalApiUrl) {
        this.api.defaults.baseURL = options.req.tenantConfig.internalApiUrl;
      }
    }
  }

  private setupInterceptors(): void {
    if (!this.api) {
      console.warn('SSRApiService: Cannot setup interceptors, axios instance not created yet');
      return;
    }

    // Add request interceptor
    this.api.interceptors.request.use(
      (config) => {
        // Forward headers from SSR request if available
        if (this.req) {
          // Forward trace ID
          if (this.req.headers['x-trace-id']) {
            config.headers['X-Trace-ID'] = this.req.headers['x-trace-id'];
          }
          // Forward user agent
          if (this.req.headers['user-agent']) {
            config.headers['User-Agent'] = `SSR/${this.req.headers['user-agent']}`;
          }
          // Add tenant domain if available
          if (this.req.tenantConfig?.apiUrl) {
            config.headers['X-Tenant-Domain'] = this.req.tenantConfig.apiUrl;
          }
        }

        this.logger.debug('SSR API Request', {
          method: config.method?.toUpperCase(),
          url: config.url,
          headers: this.sanitizeHeaders(config.headers as Record<string, string | string[]>),
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
   * Sanitizes headers to avoid logging sensitive information
   */
  private sanitizeHeaders(
    headers: Record<string, string | string[]>,
  ): Record<string, string | string[]> {
    const sanitized: Record<string, string | string[]> = {};
    const sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];

    for (const [key, value] of Object.entries(headers)) {
      if (sensitiveHeaders.includes(key.toLowerCase())) {
        sanitized[key] = '[REDACTED]';
      } else {
        sanitized[key] = value;
      }
    }

    return sanitized;
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