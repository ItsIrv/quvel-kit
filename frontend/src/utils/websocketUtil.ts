import { TenantConfig } from 'src/types/tenant.types';

export function createWebsocketConfig(configOverrides?: TenantConfig) {
  return {
    apiKey: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    apiUrl: configOverrides?.api_url ?? import.meta.env.VITE_API_URL,
  };
}
