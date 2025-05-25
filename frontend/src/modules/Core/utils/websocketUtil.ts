import { TenantConfig } from 'src/modules/Core/types/tenant.types';

export function createWebsocketConfig(configOverrides?: TenantConfig) {
  return {
    apiKey: configOverrides?.pusherAppKey ?? import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: configOverrides?.pusherAppCluster ?? import.meta.env.VITE_PUSHER_APP_CLUSTER,
  };
}
