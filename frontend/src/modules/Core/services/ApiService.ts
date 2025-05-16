import type {
  AxiosInstance,
  AxiosRequestConfig,
  AxiosResponse,
  InternalAxiosRequestConfig,
} from 'axios';
import { Service } from './Service';
import type { RegisterService } from '../types/service.types';
import { ServiceContainer } from './ServiceContainer';
import { LogService } from './LogService';

/**
 * API Service Wrapper for Axios.
 */
export class ApiService extends Service implements RegisterService {
  private readonly api: AxiosInstance;
  private log!: LogService;

  constructor(apiInstance: AxiosInstance) {
    super();

    this.api = apiInstance;
  }

  /**
   * Retrieves the internal Axios instance.
   */
  get instance(): AxiosInstance {
    return this.api;
  }

  /**
   * Registers the service.
   */
  register({ log }: ServiceContainer): void {
    this.log = log;
    this.setupInterceptors();
  }

  /**
   * Sets up request and response interceptors with logging
   */
  private setupInterceptors(): void {
    // Request interceptor
    this.api.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        const method = config.method?.toUpperCase() || 'UNKNOWN';
        const url = config.url || 'unknown-url';

        // Add trace headers to every request
        config.headers.set('X-Trace-ID', this.log.getTraceId());
        config.headers.set('X-Tenant-ID', this.log.getTraceInfo().tenant);

        this.log.info(`API Request: ${method} ${url}`, {
          method,
          url,
          baseURL: config.baseURL,
          headers: this.sanitizeHeaders(config.headers),
        });

        return config;
      },
      (error) => {
        this.log.error('API Request Error', { error: error.message });
        return Promise.reject(error as Error);
      },
    );

    // Response interceptor
    this.api.interceptors.response.use(
      (response: AxiosResponse) => {
        const method = response.config.method?.toUpperCase() || 'UNKNOWN';
        const url = response.config.url || 'unknown-url';
        const status = response.status;
        const statusText = response.statusText;

        this.log.info(`API Response: ${method} ${url} ${status} ${statusText}`, {
          method,
          url,
          status,
          statusText,
          responseTime: this.getResponseTime(response),
          contentType: response.headers['content-type'],
          contentLength: response.headers['content-length'],
        });

        return response;
      },
      (error) => {
        const config = error.config || {};
        const method = config.method?.toUpperCase() || 'UNKNOWN';
        const url = config.url || 'unknown-url';
        const status = error.response?.status || 0;
        const statusText = error.response?.statusText || '';

        this.log.error(`API Error: ${method} ${url} ${status} ${statusText}`, {
          method,
          url,
          status,
          statusText,
          message: error.message,
          stack: error.stack,
          responseData: error.response?.data,
        });

        return Promise.reject(error as Error);
      },
    );
  }

  /**
   * Sanitizes headers to avoid logging sensitive information
   */
  private sanitizeHeaders(headers: Record<string, unknown>): Record<string, unknown> {
    const sanitized: Record<string, unknown> = {};
    const allowedHeaders = [
      'X-Trace-ID',
      'X-Tenant-ID',
      'X-Tenant-Domain',
      'Cookie',
      'Accept-Language',
    ];

    for (const header of allowedHeaders) {
      if (header === 'Cookie' && headers['Cookie']) {
        sanitized[header] = (headers['Cookie'] as string)
          ?.split(';')
          .map((cookie) => cookie.split('=')[0]);
      } else {
        sanitized[header] = headers[header];
      }
    }

    return sanitized;
  }

  /**
   * Calculates response time if available
   */
  private getResponseTime(response: AxiosResponse): number | undefined {
    if (response.config.metadata?.startTime) {
      return Date.now() - response.config.metadata.startTime;
    }
    return undefined;
  }

  /**
   * Simplifies GET requests.
   */
  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.get<T>(url, config);

    return response.data;
  }

  /**
   * Simplifies POST requests.
   */
  async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.post<T>(url, data, config);
    return response.data;
  }

  /**
   * Simplifies PUT requests.
   */
  async put<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.put<T>(url, data, config);
    return response.data;
  }

  /**
   * Simplifies DELETE requests.
   */
  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.delete<T>(url, config);
    return response.data;
  }

  /**
   * Simplifies PATCH requests.
   */
  async patch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.patch<T>(url, data, config);
    return response.data;
  }
}
