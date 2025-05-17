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
// Extend axios request config to include metadata for timing
declare module 'axios' {
  export interface InternalAxiosRequestConfig {
    metadata?: {
      startTime: number;
    };
  }
}

export function createAxios(axiosConfig: AxiosRequestConfig = {}): AxiosInstance {
  const instance = axios.create(axiosConfig);

  return instance;
}

/**
 * Creates an Axios with support for making requests to the API
 * with the SSR internal request system.
 *
 * @param ssrContext
 * @param configOverrides - Optional overrides for API configuration.
 * @returns An Axios instance.
 */
export function createApi(
  ssrContext?: QSsrContext | null,
  configOverrides?: TenantConfig & { internalApiUrl?: string },
): AxiosInstance {
  // In order: Internal API URL, Public API URL, Vite API URL
  const baseURL =
    configOverrides?.internalApiUrl ?? configOverrides?.apiUrl ?? process.env.VITE_API_URL ?? '';

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
    const sessionToken = cookies.get(configOverrides?.sessionName ?? SessionName);

    // Attach session cookie (for authentication)
    api.defaults.headers['Host'] = '';
    api.defaults.maxRedirects = 0;

    if (isValidSessionToken(sessionToken)) {
      api.defaults.headers.Cookie = `${SessionName}=${sessionToken}`;
    }

    api.defaults.headers['X-Tenant-Domain'] = configOverrides?.apiUrl ?? '';
    api.defaults.headers['X-SSR-Key'] = process.env.SSR_API_KEY ?? '';
  } else {
    // TODO: On browser, add interceptors for XSRF expired/missing
  }

  return api;
}

/**
 * Validates a session token.
 * @param token - The token to validate.
 * @returns True if the token is valid, false otherwise.
 */
export function isValidSessionToken(token: unknown): token is string {
  if (typeof token !== 'string') return false;

  try {
    const decoded = decodeURIComponent(token);

    return /^[A-Za-z0-9+/=]{20,512}$/.test(decoded);
  } catch {
    return false;
  }
}
