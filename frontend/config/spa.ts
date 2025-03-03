import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  const isLocal = process.env.LOCAL === '1';

  return {
    build: {
      vueRouterMode: 'history',
    },
    devServer: {
      strictPort: true,
      port: isLocal ? 3001 : 9002,
      host: isLocal ? 'second-tenant.quvel.127.0.0.1.nip.io' : '0.0.0.0',
      https: getCerts(),
    },
  };
});
