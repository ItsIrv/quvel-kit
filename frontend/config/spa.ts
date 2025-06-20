import { defineConfig } from '#q-app/wrappers';
import { getCerts, isLocal, config } from './utils';

export default defineConfig(() => {
  return {
    build: {
      vueRouterMode: 'history',
    },
    devServer: {
      strictPort: true,
      port: isLocal() ? config.infra.getPort('spa', 'dev') : config.infra.getPort('spa', 'vite'),
      host: config.infra.getHost(isLocal() ? 'dev' : 'prod'),
      https: getCerts(),
    },
  };
});
