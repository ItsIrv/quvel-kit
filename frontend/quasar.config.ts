// Configuration for your app
// https://v2.quasar.dev/quasar-cli-vite/quasar-config-file

import { defineConfig } from '#q-app/wrappers';
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';

export default defineConfig((ctx) => {
  return {
    // https://v2.quasar.dev/quasar-cli-vite/prefetch-feature
    preFetch: true,
    boot: ['i18n', 'axios'],
    css: ['app.scss'],
    extras: ['eva-icons', 'roboto-font'],
    build: {
      target: {
        browser: ['es2022', 'firefox115', 'chrome115', 'safari14'],
        node: 'node20',
      },
      typescript: {
        strict: true,
        vueShim: true,
      },
      vueRouterMode: 'history',
      vitePlugins: [
        [
          '@intlify/unplugin-vue-i18n/vite',
          {
            // if you want to use named tokens in your Vue I18n messages, such as 'Hello {name}',
            // you need to set `runtimeOnly: false`
            // runtimeOnly: false,
            ssr: ctx.modeName === 'ssr',
            // you need to set i18n resource including paths !
            include: [fileURLToPath(new URL('./src/i18n', import.meta.url))],
          },
        ],

        [
          'vite-plugin-checker',
          {
            vueTsc: true,
            eslint: {
              lintCommand: 'eslint -c ./eslint.config.js "./src*/**/*.{ts,js,mjs,cjs,vue}"',
              useFlatConfig: true,
            },
          },
          { server: false },
        ],
        {
          name: 'client-host',
          transform(code, id) {
            if (id.endsWith('dist/client/client.mjs') || id.endsWith('dist/client/env.mjs')) {
              return code.replace('__HMR_HOSTNAME__', JSON.stringify('quvel.127.0.0.1.nip.io'));
            }

            return code;
          },
        },
      ],
      extendViteConf(viteConf) {
        viteConf.server = {
          ...viteConf.server,
          allowedHosts: ['localhost', '127.0.0.1', 'quvel.127.0.0.1.nip.io'],
          strictPort: true,
          port: 9001,
          host: '0.0.0.0',
          watch: {
            usePolling: true,
          },
          hmr: {
            protocol: 'wss',
            host: '0.0.0.0',
            port: 9001,
            clientPort: 443,
            path: '/hmr',
          },
          https: {
            key: readFileSync('/certs/selfsigned.key'),
            cert: readFileSync('/certs/selfsigned.crt'),
            ca: readFileSync('/certs/ca.pem'),
          },
        };
      },
    },
    devServer: {
      strictPort: true,
      port: 9000,
      host: '0.0.0.0',
      https: {
        key: readFileSync('/certs/selfsigned.key'),
        cert: readFileSync('/certs/selfsigned.crt'),
        ca: readFileSync('/certs/ca.pem'),
      },
    },
    framework: {
      config: {},
      plugins: ['Cookies'],
    },
    animations: [],
    ssr: {
      prodPort: 9000,
      middlewares: [
        'render', // keep this as last one
      ],
      pwa: false,
    },
    capacitor: {
      hideSplashscreen: true,
    },
  };
});
