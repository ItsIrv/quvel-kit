import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import type { QSsrContext } from '@quasar/app-vite';
import { SessionName } from 'src/models/Session';

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

    // Attach cookies (for session auth)
    api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
    api.defaults.headers['Host'] = '';
  }

  return api;
}
