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
      ? (configOverrides?.apiUrl ?? '') // When hosting SSR on local machine don't use internalApiUrl
      : (configOverrides?.apiUrl ?? process.env.VITE_API_URL ?? '');

  const axiosConfig: AxiosRequestConfig = {
    baseURL,
    withCredentials: true,
    withXSRFToken: true,
    headers: {
      Accept: 'application/json',
    },
  };

  const api = createAxios(axiosConfig);

  if (ssrContext) {
    const cookies = Cookies.parseSSR(ssrContext);
    const sessionToken = cookies.get(SessionName);

    // Attach session cookie (for authentication)
    api.defaults.headers['Host'] = '';

    if (isValidSessionToken(sessionToken)) {
      api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
    }
  }

  return api;
}

/**
 * Validates a session token.
 * @param token - The token to validate.
 * @returns True if the token is valid, false otherwise.
 */
function isValidSessionToken(token: unknown): token is string {
  return typeof token === 'string' && /^[A-Za-z0-9-_]{20,512}$/.test(token);
}
