import axios, { type AxiosResponse, type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import type { QSsrContext } from '@quasar/app-vite';
import { SessionName } from 'src/models/Session';
import { showNotification } from 'src/utils/notifyUtil';
import { ApiService } from 'src/services/ApiService';

const isServer = typeof window === 'undefined';

/**
 * Default Axios configuration.
 */
const axiosConfig: AxiosRequestConfig = {
  baseURL: isServer ? (process.env.VITE_API_INTERNAL_URL ?? '') : (process.env.VITE_API_URL ?? ''),
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
};

/**
 * Creates an Axios instance with the given configuration.
 *
 * @param axiosConfig - The configuration for the Axios instance.
 * @returns An Axios instance.
 */
export function createAxios(axiosConfig: AxiosRequestConfig): AxiosInstance {
  return axios.create(axiosConfig);
}

/**
 * Creates an Axios instance with global error handling.
 * @param ssrContext - The SSR context, if in SSR mode.
 * @returns An axios instance configured to work with the Quvel API.
 */
export function createApi(ssrContext?: QSsrContext | null): AxiosInstance {
  const api = createAxios(axiosConfig);

  if (ssrContext) {
    const cookies = Cookies.parseSSR(ssrContext);
    const sessionToken = cookies.get(SessionName);

    api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
  }

  // Request Interceptor (Modify requests before sending)
  api.interceptors.request.use(
    (config) => {
      return config;
    },
    (error) => Promise.reject(new Error(error)),
  );

  // Response Interceptor (Global error handling)
  api.interceptors.response.use(
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
          if (!isServer) window.location.href = '/login';
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

  return api;
}

/**
 * Creates an instance of the ApiService with the provided SSR context.
 * @param ssrContext - The SSR context, if applicable.
 * @returns An instance of the ApiService.
 */
export function createApiService(ssrContext?: QSsrContext | null): ApiService {
  return new ApiService(createApi(ssrContext));
}
