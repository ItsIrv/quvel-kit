import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  const isLocal = process.env.LOCAL === '1';
  const isMultiTenant = process.env.SSR_MULTI_TENANT === 'true';

  return {
    boot: [
      // Add tenant config boot file for SPA mode only in multi-tenant setups
      ...(isMultiTenant ? ['tenant-config'] : []),
      'container',
      {
        server: false,
        path: 'pinia-hydrator',
      },
    ],
    build: {
      vueRouterMode: 'history',
    },
    devServer: {
      strictPort: true,
      port: isLocal ? 3001 : 9002,
      host: isLocal ? 'quvel.127.0.0.1.nip.io' : '0.0.0.0',
      https: getCerts(),
    },
  };
});
