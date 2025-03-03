import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  return {
    devServer: {
      strictPort: true,
      port: 3003,
      host: 'second-tenant.quvel.127.0.0.1.nip.io',
      https: getCerts(),
    },
  };
});
