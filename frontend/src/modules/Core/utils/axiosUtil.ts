import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';
import { Cookies } from 'quasar';
import { SessionName } from 'src/modules/Auth/models/Session';
import { SsrServiceOptions } from '../types/service.types';

export function createAxios(axiosConfig: AxiosRequestConfig = {}): AxiosInstance {
  const instance = axios.create(axiosConfig);

  return instance;
}

/**
 * Gets the tenant-specific XSRF cookie name
 */
function getTenantXsrfCookieName(ssrServiceOptions?: SsrServiceOptions | null): string {
  const tenantId =
    ssrServiceOptions?.req?.tenantConfig?.tenantId ??
    (typeof window !== 'undefined' ? window.__TENANT_CONFIG__?.tenantId : null);

  if (tenantId) {
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

  if (ssrServiceOptions?.req?.tenantConfig) {
    baseURL =
      ssrServiceOptions?.req?.tenantConfig?.internalApiUrl ??
      ssrServiceOptions?.req?.tenantConfig?.apiUrl;
  } else {
    baseURL =
      (typeof window !== 'undefined' ? window.__TENANT_CONFIG__?.apiUrl : null) ??
      process.env.VITE_API_URL ??
      '';
  }

  if (!baseURL) {
    throw new Error('No API URL found');
  }

  console.log(baseURL);

  const axiosConfig: AxiosRequestConfig = {
    baseURL,
    withCredentials: true,
    withXSRFToken: true,
    xsrfCookieName: getTenantXsrfCookieName(ssrServiceOptions),
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
      Accept: 'application/json',
    },
  };

  const api = createAxios(axiosConfig);

  if (ssrServiceOptions) {
    const cookies = Cookies.parseSSR(ssrServiceOptions);
    const sessionCookie = ssrServiceOptions.req?.tenantConfig?.sessionCookie ?? SessionName;
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

    api.defaults.headers['X-Tenant-Domain'] = ssrServiceOptions.req?.tenantConfig?.apiUrl ?? '';
    api.defaults.headers['X-SSR-Key'] = process.env.SSR_API_KEY ?? '';
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
