import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';

/**
 * Creates an Axios instance with the given configuration.
 *
 * @param axiosConfig - The configuration for the Axios instance.
 * @returns An Axios instance.
 */
export function createAxios(axiosConfig: AxiosRequestConfig = {}): AxiosInstance {
  // Attach API key in SSR mode (env vars without VITE_ prefix are not accessible in the browser)
  if (process.env.SSR_API_KEY) {
    // Tell the backend it's an internal request
    axiosConfig.headers = axiosConfig.headers || {};
    axiosConfig.headers['X-SSR-Key'] = process.env.SSR_API_KEY;
  }

  return axios.create(axiosConfig);
}
