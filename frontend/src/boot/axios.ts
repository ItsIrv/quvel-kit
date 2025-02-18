import { defineBoot } from '#q-app/wrappers'
import type { QSsrContext } from '@quasar/app-vite'
import axios, { type AxiosRequestConfig, type AxiosInstance } from 'axios'
import { Cookies } from 'quasar'

/**
 * Declares Axios instances for both client and server contexts.
 */
declare module 'vue' {
  interface ComponentCustomProperties {
    $axios: AxiosInstance
    $api: AxiosInstance
  }
}

/**
 * Determines if the execution context is the server.
 */
const isServer = typeof window === 'undefined'

/**
 * Default Axios configuration.
 */
const axiosConfig: AxiosRequestConfig = {
  baseURL: (isServer ? process.env.VITE_API_INTERNAL_URL : process.env.VITE_API_URL) || '',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
}

/**
 * Creates an Axios instance with the appropriate configuration based on the execution context.
 */
export function createApi(ssrContext?: QSsrContext | null): AxiosInstance {
  if (ssrContext) {
    const cookies = Cookies.parseSSR(ssrContext)
    const sessionToken = cookies.get('quvel_session')

    return axios.create({
      ...axiosConfig,
      headers: {
        ...axiosConfig.headers,
        Cookie: `quvel_session=${sessionToken}`,
      },
    })
  } else {
    return axios.create(axiosConfig)
  }
}

export default defineBoot(() => {
  //
})
