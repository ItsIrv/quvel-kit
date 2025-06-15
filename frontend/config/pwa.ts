import { defineConfig } from '#q-app/wrappers';
import { getCerts, isLocal as isLocalFn } from './utils';

const isLocal = isLocalFn();

export default defineConfig(() => {
  return {
    build: {
      extendViteConf(viteConf): void {
        viteConf.server = {
          ...viteConf.server,
          allowedHosts: ['quvel.127.0.0.1.nip.io'],
          strictPort: true,
          port: 9002,
          host: '0.0.0.0',
          watch: {
            usePolling: true,
          },
          hmr: {
            protocol: 'wss',
            host: isLocal ? 'quvel.127.0.0.1.nip.io' : '0.0.0.0',
            port: 9002,
            clientPort: isLocal ? 9002 : 443,
            path: '/hmr',
          },
          https: getCerts(),
        };
      },
    },
    devServer: {
      strictPort: true,
      port: isLocal ? 3003 : 9002,
      host: isLocal ? 'quvel.127.0.0.1.nip.io' : '0.0.0.0',
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
        json.name = 'QuVel Kit';
        json.short_name = 'QuVel';
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
