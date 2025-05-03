import type { AxiosInstance, AxiosRequestConfig } from 'axios';
import { Service } from './Service';
import { BootableService } from '../types/service.types';
import { ServiceContainer } from './ServiceContainer';

/**
 * API Service Wrapper for Axios.
 */
export class ApiService extends Service implements BootableService {
  private readonly api: AxiosInstance;

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
   * Boots the service.
   */
  register({ config }: ServiceContainer): void {
    if (config.get('appUrl') === this.api.defaults.baseURL) {
      throw new Error(
        'API URL matches app URL, this will cause infinite redirects. Please check your configuration.',
      );
    }
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
