import { defineConfig } from '#q-app/wrappers';
import { getCerts, isLocal, config } from './utils';

/**
 * Quasar SSR Configuration
 */
export default defineConfig(() => {
  const hmrConfig = config.infra.getHMR();
  const vitePort = config.infra.getPort('ssr', 'vite');
  
  return {
    build: {
      extendViteConf(viteConf): void {
        viteConf.server = {
          ...viteConf.server,
          allowedHosts: config.infra.getAllowedHosts(),
          strictPort: true,
          port: vitePort,
          host: config.infra.getHost('prod'),
          watch: {
            usePolling: true,
          },
          hmr: {
            protocol: 'wss',
            host: isLocal() ? config.infra.getHost('dev') : config.infra.getHost('prod'),
            port: vitePort,
            clientPort: isLocal() ? vitePort : hmrConfig.clientPort,
            path: '/hmr',
          },
          https: getCerts(),
        };
      },
    },
    devServer: {
      strictPort: true,
      port: config.infra.getPort('ssr', 'dev'),
      host: config.infra.getHost(isLocal() ? 'dev' : 'prod'),
      https: getCerts(),
      open: false,
    },
    ssr: {
      prodPort: config.infra.getPort('ssr', 'prod'),
      middlewares: [
        'render', // keep this as last one
      ],
      // Enable PWA in SSR mode when requested
      pwa: config.getBoolean('SSR_PWA', false),
    },
    pwa: {
      injectPwaMetaTags: config.getBoolean('SSR_PWA', false),
    },
  };
});
