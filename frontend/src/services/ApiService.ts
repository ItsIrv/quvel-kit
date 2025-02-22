import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';
import { showNotification } from 'src/utils/notifyUtil';

/**
 * API Service Wrapper for Axios.
 */
export class ApiService {
  private readonly api: AxiosInstance;

  constructor(apiInstance: AxiosInstance) {
    this.api = apiInstance;
    this.setupInterceptors();
  }

  /**
   * Retrieves the internal Axios instance.
   */
  get instance(): AxiosInstance {
    return this.api;
  }

  /**
   * Sets up request and response interceptors.
   */
  private setupInterceptors(): void {
    if (typeof window === 'undefined') return;

    this.api.interceptors.request.use(
      (config) => config,
      (error) => Promise.reject(new Error(error)),
    );

    this.api.interceptors.response.use(
      (response) => response,
      async (error) => {
        const { response } = error;

        if ((response as AxiosResponse).status === undefined) {
          showNotification('negative', 'Network error, check your connection.');
          return Promise.reject(new Error('Network error, check your connection.'));
        }

        switch (response.status) {
          case 401: // Unauthorized (Auto-logout)
            showNotification('negative', 'Session expired, please log in again.');
            if (typeof window !== 'undefined') window.location.href = '/login';
            break;

          case 403: // Forbidden
            showNotification('warning', 'You do not have permission for this action.');
            break;

          case 500: // Server Errors
          case 503:
            showNotification('negative', 'Server error, please try later.');
            break;

          default:
            showNotification('negative', response.data?.message ?? 'An error occurred.');
        }

        return Promise.reject(new Error(response.data?.message ?? 'An error occurred.'));
      },
    );
  }

  /**
   * Simplifies GET requests.
   * @param url - The API endpoint.
   * @param config - Optional Axios config.
   * @returns The response data.
   */
  async get<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.get<T>(url, config);
    return response.data;
  }

  /**
   * Simplifies POST requests.
   * @param url - The API endpoint.
   * @param data - The request payload.
   * @param config - Optional Axios config.
   * @returns The response data.
   */
  async post<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.post<T>(url, data, config);
    return response.data;
  }

  /**
   * Simplifies PUT requests.
   * @param url - The API endpoint.
   * @param data - The request payload.
   * @param config - Optional Axios config.
   * @returns The response data.
   */
  async put<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.put<T>(url, data, config);
    return response.data;
  }

  /**
   * Simplifies DELETE requests.
   * @param url - The API endpoint.
   * @param config - Optional Axios config.
   * @returns The response data.
   */
  async delete<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.delete<T>(url, config);
    return response.data;
  }

  /**
   * Simplifies PATCH requests.
   * @param url - The API endpoint.
   * @param data - The request payload.
   * @param config - Optional Axios config.
   * @returns The response data.
   */
  async patch<T>(url: string, data?: unknown, config?: AxiosRequestConfig): Promise<T> {
    const response = await this.api.patch<T>(url, data, config);
    return response.data;
  }
}
