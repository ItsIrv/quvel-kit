import { defineConfig } from '#q-app/wrappers';
import { getCerts, isLocal, config } from './utils';

export default defineConfig(() => {
  const hmrConfig = config.infra.getHMR();
  const vitePort = config.infra.getPort('pwa', 'vite');
  
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
      port: config.infra.getPort('pwa', 'dev'),
      host: config.infra.getHost(isLocal() ? 'dev' : 'prod'),
      https: getCerts(),
      open: false,
    },
    pwa: {
      workboxMode: 'GenerateSW',
      injectPwaMetaTags: true,
      swFilename: 'sw.js',
      manifestFilename: 'manifest.json',
      useCredentialsForManifestTag: false,
      extendGenerateSWOptions(cfg) {
        // Configure Workbox service worker generation
        cfg.cleanupOutdatedCaches = true;
        cfg.skipWaiting = true;
        cfg.clientsClaim = true;

        // Add cache strategies for different resource types
        cfg.runtimeCaching = [
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 30 * 24 * 60 * 60, // 30 days
              },
            },
          },
        ];
      },
      extendInjectManifestOptions(cfg) {
        // If using injectManifest mode
      },
      extendManifestJson(json) {
        // Customize manifest.json
        json.name = config.app.getName();
        json.short_name = config.app.getShortName();
        json.description = 'Full-stack SaaS framework combining Laravel with Vue 3 + Quasar';
        json.display = 'standalone';
        json.orientation = 'portrait';
        json.background_color = '#ffffff';
        json.theme_color = '#027be3';
        json.categories = ['productivity', 'business'];
      },
      extendPWACustomSWConf(esbuildConf) {
        // Customize service worker build
      },
    },
  };
});
