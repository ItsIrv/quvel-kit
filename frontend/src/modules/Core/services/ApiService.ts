import type { AxiosInstance, AxiosResponse } from 'axios';
import type { RegisterService, SsrServiceOptions, SsrAwareService } from '../types/service.types';
import { AxiosRequestConfig, InternalAxiosRequestConfig } from '../types/axios.types';
import { Service } from './Service';
import { ServiceContainer } from './ServiceContainer';
import { LogService } from './LogService';
import { createApi } from '../utils/axiosUtil';

/**
 * API Service Wrapper for Axios.
 */
export class ApiService extends Service implements SsrAwareService, RegisterService {
  private api!: AxiosInstance;
  private log!: LogService;
  private abortControllers: Map<string, AbortController> = new Map();
  private requestCounter = 0;

  /**
   * Retrieves the internal Axios instance.
   */
  get instance(): AxiosInstance {
    return this.api;
  }

  /**
   * Boot method to initialize with SSR context if available.
   */
  boot(ssrServiceOptions?: SsrServiceOptions): void {
    // Create API instance with SSR context if available
    this.api = createApi(ssrServiceOptions);
  }

  /**
   * Registers the service.
   */
  register(container: ServiceContainer): void {
    this.log = container.log;

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
        config.headers.set('X-Trace-ID', this.log.getLogger().getTraceInfo().id);
        config.headers.set('X-Tenant-ID', this.log.getLogger().getTraceInfo().tenant);

        this.log.info(`API Request: ${method} ${url}`, {
          method,
          url,
          baseURL: config.baseURL,
          headers: this.sanitizeHeaders(config.headers),
        });

        // Add metadata for response time tracking
        config.metadata = { startTime: Date.now() };

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
          responseTimeMs: this.getResponseTime(response),
          contentType: response.headers['content-type'],
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
          responseTimeMs: error.config?.metadata?.startTime
            ? Date.now() - error.config.metadata.startTime
            : undefined,
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
      'X-SSR-Key',
      'Cookie',
      'Accept-Language',
    ];

    for (const header of allowedHeaders) {
      if (header === 'Cookie' && headers['Cookie']) {
        sanitized[header] = (headers['Cookie'] as string)
          ?.split(';')
          .map((cookie) => cookie.split('=')[0]);
      } else if (header === 'X-SSR-Key' && headers['X-SSR-Key']) {
        sanitized[header] = 'REDACTED';
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
   * Creates a unique request ID for tracking abort controllers
   * Uses an incrementing counter to ensure uniqueness and track total requests
   */
  private createRequestId(url: string, method: string): string {
    this.requestCounter += 1;
    return `${method}:${url}:${this.requestCounter}`;
  }

  /**
   * Creates a cancellable request configuration
   *
   * @param requestId - Unique identifier for the request
   * @param config - Original request configuration
   * @returns Enhanced configuration with abort signal
   */
  private createCancellableRequest(
    requestId: string,
    config?: AxiosRequestConfig,
  ): AxiosRequestConfig {
    // Clean up any existing controller for this request ID
    this.cancelRequest(requestId);

    // Create a new abort controller
    const controller = new AbortController();
    this.abortControllers.set(requestId, controller);

    // Merge the signal with the existing config
    return {
      ...config,
      signal: controller.signal,
    };
  }

  /**
   * Cancels a specific request by ID
   *
   * @param requestId - ID of the request to cancel
   * @returns True if a request was cancelled, false otherwise
   */
  public cancelRequest(requestId: string): boolean {
    const controller = this.abortControllers.get(requestId);
    if (controller) {
      controller.abort();
      this.abortControllers.delete(requestId);
      this.log.info(`Cancelled request: ${requestId}`);
      return true;
    }
    return false;
  }

  /**
   * Cancels all pending requests
   *
   * @returns Number of requests cancelled
   */
  public cancelAllRequests(): number {
    let count = 0;
    this.abortControllers.forEach((controller, requestId) => {
      controller.abort();
      this.log.info(`Cancelled request: ${requestId}`);
      count++;
    });
    this.abortControllers.clear();
    return count;
  }

  /**
   * Simplifies GET requests.
   *
   * @param url - Request URL
   * @param config - Request configuration
   * @param requestId - Optional request ID for cancellation (auto-generated if not provided)
   * @returns Promise resolving to the response data
   */
  async get<T>(url: string, config?: AxiosRequestConfig, requestId?: string): Promise<T> {
    const id = requestId || this.createRequestId(url, 'GET');
    const cancellableConfig = this.createCancellableRequest(id, config);

    try {
      const response = await this.api.get<T>(url, cancellableConfig);
      this.abortControllers.delete(id);
      return response.data;
    } catch (error) {
      this.abortControllers.delete(id);
      throw error;
    }
  }

  /**
   * Simplifies POST requests.
   *
   * @param url - Request URL
   * @param data - Request payload
   * @param config - Request configuration
   * @param requestId - Optional request ID for cancellation (auto-generated if not provided)
   * @returns Promise resolving to the response data
   */
  async post<T>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
    requestId?: string,
  ): Promise<T> {
    const id = requestId || this.createRequestId(url, 'POST');
    const cancellableConfig = this.createCancellableRequest(id, config);

    try {
      const response = await this.api.post<T>(url, data, cancellableConfig);
      this.abortControllers.delete(id);
      return response.data;
    } catch (error) {
      this.abortControllers.delete(id);
      throw error;
    }
  }

  /**
   * Simplifies PUT requests.
   *
   * @param url - Request URL
   * @param data - Request payload
   * @param config - Request configuration
   * @param requestId - Optional request ID for cancellation (auto-generated if not provided)
   * @returns Promise resolving to the response data
   */
  async put<T>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
    requestId?: string,
  ): Promise<T> {
    const id = requestId || this.createRequestId(url, 'PUT');
    const cancellableConfig = this.createCancellableRequest(id, config);

    try {
      const response = await this.api.put<T>(url, data, cancellableConfig);
      this.abortControllers.delete(id);
      return response.data;
    } catch (error) {
      this.abortControllers.delete(id);
      throw error;
    }
  }

  /**
   * Simplifies DELETE requests.
   *
   * @param url - Request URL
   * @param config - Request configuration
   * @param requestId - Optional request ID for cancellation (auto-generated if not provided)
   * @returns Promise resolving to the response data
   */
  async delete<T>(url: string, config?: AxiosRequestConfig, requestId?: string): Promise<T> {
    const id = requestId || this.createRequestId(url, 'DELETE');
    const cancellableConfig = this.createCancellableRequest(id, config);

    try {
      const response = await this.api.delete<T>(url, cancellableConfig);
      this.abortControllers.delete(id);
      return response.data;
    } catch (error) {
      this.abortControllers.delete(id);
      throw error;
    }
  }

  /**
   * Simplifies PATCH requests.
   *
   * @param url - Request URL
   * @param data - Request payload
   * @param config - Request configuration
   * @param requestId - Optional request ID for cancellation (auto-generated if not provided)
   * @returns Promise resolving to the response data
   */
  async patch<T>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig,
    requestId?: string,
  ): Promise<T> {
    const id = requestId || this.createRequestId(url, 'PATCH');
    const cancellableConfig = this.createCancellableRequest(id, config);

    try {
      const response = await this.api.patch<T>(url, data, cancellableConfig);
      this.abortControllers.delete(id);
      return response.data;
    } catch (error) {
      this.abortControllers.delete(id);
      throw error;
    }
  }
}
