import type {
  AxiosRequestConfig as BaseAxiosRequestConfig,
  InternalAxiosRequestConfig as BaseInternalAxiosRequestConfig,
} from 'axios';

/**
 * Extended Axios request config with metadata
 */
export interface AxiosRequestConfig extends BaseAxiosRequestConfig {
  metadata?: {
    startTime: number;
    [key: string]: unknown;
  };
  signal?: AbortSignal;
}

/**
 * Extended internal Axios request config with metadata
 */
export interface InternalAxiosRequestConfig extends BaseInternalAxiosRequestConfig {
  metadata?: {
    startTime: number;
    [key: string]: unknown;
  };
}
