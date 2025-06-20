import { defineConfig } from '#q-app/wrappers';
import { getCerts, config } from './utils';

export default defineConfig(() => {
  return {
    devServer: {
      allowedHosts: config.infra.getAllowedHosts(),
      strictPort: true,
      port: config.infra.getPort('capacitor', 'dev'),
      host: config.infra.getHost('dev'),
      https: getCerts(),
      open: false,
    },
    capacitor: {
      hideSplashscreen: true,
      appName: config.app.getId(),
      description:
        'A Laravel & Quasar hybrid framework optimized for SSR and seamless development.',
      version: '0.1.3-beta',
    },
  };
});
