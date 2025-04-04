import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import type { QSsrContext } from '@quasar/app-vite';
import { SessionName } from 'src/modules/Auth/models/Session';
import { TenantConfig } from 'src/modules/Core/types/tenant.types';

/**
 * Creates an Axios instance with the given configuration.
 *
 * @param axiosConfig - The configuration for the Axios instance.
 * @returns An Axios instance.
 */
export function createAxios(axiosConfig: AxiosRequestConfig = {}): AxiosInstance {
  return axios.create(axiosConfig);
}

/**
 * Creates an Axios instance with the given configuration.
 *
 * @param ssrContext
 * @param configOverrides - Optional overrides for API configuration.
 * @returns An Axios instance.
 */
export function createApi(
  ssrContext?: QSsrContext | null,
  configOverrides?: TenantConfig,
): AxiosInstance {
  const baseURL =
    ssrContext !== null
      ? // ? (configOverrides?.internal_api_url ?? '')
        (configOverrides?.api_url ?? '') // When hosting SSR on local machine don't use docker url
      : (configOverrides?.api_url ?? process.env.VITE_API_URL ?? '');

  const axiosConfig: AxiosRequestConfig = {
    baseURL,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
      Accept: 'application/json',
    },
  };

  const api = axios.create(axiosConfig);

  if (ssrContext) {
    const cookies = Cookies.parseSSR(ssrContext);
    const sessionToken = cookies.get(SessionName);

    // Attach session cookie (for authentication)
    api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
    api.defaults.headers['Host'] = '';
  }

  return api;
}
