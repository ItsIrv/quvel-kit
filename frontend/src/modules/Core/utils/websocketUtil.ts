import { TenantConfig } from 'src/modules/Core/types/tenant.types';

export function createWebsocketConfig(configOverrides?: TenantConfig) {
  return {
    apiKey: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    apiUrl: configOverrides?.apiUrl ?? import.meta.env.VITE_API_URL,
  };
}
