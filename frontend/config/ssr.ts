import { defineConfig } from '#q-app/wrappers';
import { getCerts, isLocal as isLocalFn } from './utils';

const isLocal = isLocalFn();

/**
 * Quasar SSR Configuration
 */
export default defineConfig(() => {
  return {
    build: {
      extendViteConf(viteConf): void {
        viteConf.server = {
          ...viteConf.server,
          allowedHosts: ['quvel.127.0.0.1.nip.io'],
          strictPort: true,
          port: 9001,
          host: '0.0.0.0',
          watch: {
            usePolling: true,
          },
          hmr: {
            protocol: 'wss',
            host: isLocal ? 'quvel.127.0.0.1.nip.io' : '0.0.0.0',
            port: 9001,
            clientPort: isLocal ? 9001 : 443,
            path: '/hmr',
          },
          https: getCerts(),
        };
      },
    },
    devServer: {
      strictPort: true,
      port: isLocal ? 3000 : 9000,
      host: isLocal ? 'quvel.127.0.0.1.nip.io' : '0.0.0.0',
      https: getCerts(),
      open: false,
    },
    ssr: {
      prodPort: 3000,
      middlewares: [
        'render', // keep this as last one
      ],
      pwa: Boolean(process.env.SSR_PWA),
    },
  };
});
