import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  return {
    build: {
      // extendViteConf(viteConf): void {
      //   viteConf.server = {
      //     ...viteConf.server,
      //     allowedHosts: ['second-tenant.quvel.127.0.0.1.nip.io', 'quvel.127.0.0.1.nip.io'],
      //     strictPort: true,
      //     port: 9001,
      //     host: '0.0.0.0',
      //     watch: {
      //       usePolling: true,
      //     },
      //     hmr: {
      //       protocol: 'wss',
      //       host: isLocal ? 'second-tenant.quvel.127.0.0.1.nip.io' : '0.0.0.0',
      //       port: 9001,
      //       clientPort: isLocal ? 9001 : 443,
      //       path: '/hmr',
      //     },
      //     https: getCerts(),
      //   };
      // },
    },
    devServer: {
      strictPort: true,
      port: 3001,
      host: 'quvel.127.0.0.1.nip.io',
      https: getCerts(),
    },
    capacitor: {
      hideSplashscreen: true,
      appName: 'quvel.irv.codes',
      description:
        'A Laravel & Quasar hybrid framework optimized for SSR and seamless development.',
      version: '0.1.3-beta',
    },
  };
});
