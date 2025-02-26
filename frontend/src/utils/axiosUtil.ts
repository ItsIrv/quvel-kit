import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import type { QSsrContext } from '@quasar/app-vite';
import { SessionName } from 'src/models/Session';
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
    const xsrfToken = cookies.get('XSRF-TOKEN');

    // Attach cookies (for session auth)
    api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
    api.defaults.headers['Host'] = process.env.VITE_API_HOST ?? '';

    // Attach X-XSRF-TOKEN header
    if (xsrfToken !== null) {
      api.defaults.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
    }
  }

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
