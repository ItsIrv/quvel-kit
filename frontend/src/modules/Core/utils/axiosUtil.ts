import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import { SessionName } from 'src/modules/Auth/models/Session';
import { SsrServiceOptions } from '../types/service.types';

export function createAxios(axiosConfig: AxiosRequestConfig = {}): AxiosInstance {
  const instance = axios.create(axiosConfig);

  return instance;
}

/**
 * Gets the app-specific XSRF cookie name
 */
function getAppXsrfCookieName(ssrServiceOptions?: SsrServiceOptions | null): string {
  // Check for tenant ID first (for multi-tenant setups)
  const ssrConfig = ssrServiceOptions?.req?.ssrContext?.appConfig as unknown as Record<string, unknown>;
  const windowConfig = typeof window !== 'undefined' ? window.__APP_CONFIG__ as unknown as Record<string, unknown> : null;
  
  const tenantId =
    ssrConfig?.tenantId ??
    windowConfig?.tenantId;

  if (tenantId && typeof tenantId === 'string') {
    return `XSRF-TOKEN-${tenantId}`;
  }

  return 'XSRF-TOKEN';
}

/**
 * Creates an Axios with support for making requests to the API
 * with the SSR internal request system.
 *
 * @param ssrServiceOptions
 * @returns An Axios instance.
 */
export function createApi(ssrServiceOptions?: SsrServiceOptions | null): AxiosInstance {
  // In order: Internal API URL, Public API URL, Vite API URL
  let baseURL = '';

  if (ssrServiceOptions?.req?.ssrContext?.appConfig) {
    const config = ssrServiceOptions?.req?.ssrContext.appConfig as unknown as Record<string, unknown>;
    baseURL =
      (config?.internalApiUrl as string) ??
      ssrServiceOptions?.req?.ssrContext.appConfig?.apiUrl;
  } else {
    baseURL =
      (typeof window !== 'undefined' ? window.__APP_CONFIG__?.apiUrl : null) ??
      process.env.VITE_API_URL ??
      '';
  }

  if (!baseURL) {
    throw new Error('No API URL found');
  }

  const axiosConfig: AxiosRequestConfig = {
    baseURL,
    withCredentials: true,
    withXSRFToken: true,
    xsrfCookieName: getAppXsrfCookieName(ssrServiceOptions),
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
      Accept: 'application/json',
    },
  };

  const api = createAxios(axiosConfig);

  if (ssrServiceOptions) {
    const cookies = Cookies.parseSSR(ssrServiceOptions);
    const sessionCookie = ssrServiceOptions.req?.ssrContext?.appConfig?.sessionCookie ?? SessionName;
    const sessionToken = cookies.get(sessionCookie);

    // Attach session cookie (for authentication)
    api.defaults.headers['Host'] = '';
    api.defaults.maxRedirects = process.env.SSR_AXIOS_MAX_REDIRECTS
      ? Number(process.env.SSR_AXIOS_MAX_REDIRECTS)
      : 0;
    api.defaults.timeout = process.env.SSR_AXIOS_TIMEOUT
      ? Number(process.env.SSR_AXIOS_TIMEOUT)
      : 5000;

    if (isValidSessionToken(sessionToken)) {
      api.defaults.headers.Cookie = `${sessionCookie}=${sessionToken}`;
    }
  } else {
    // TODO: On browser, add interceptors for XSRF expired/missing
  }

  if (process.env.MODE === 'capacitor') {
    api.defaults.headers['X-Capacitor'] = 'true';
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
