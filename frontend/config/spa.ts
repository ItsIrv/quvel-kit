import { defineConfig } from '#q-app/wrappers';

export default defineConfig(() => {
  const isLocal = process.env.LOCAL === '1';

  return {
    build: {
      vueRouterMode: 'history',
    },
    devServer: {
      port: isLocal ? 3000 : 9000,
      host: isLocal ? 'second-tenant.quvel.127.0.0.1.nip.io' : '0.0.0.0',
    },
  };
});
