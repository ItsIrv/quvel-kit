import { defineConfig } from '#q-app/wrappers';
import { fileURLToPath } from 'url';
import { isLocal } from './utils';

export default defineConfig((ctx) => {
  return {
    preFetch: true,
    boot: ['container'],
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
            ssr: ctx.modeName === 'ssr',
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
          transform(code, id): string {
            if (isLocal()) {
              return code;
            }

            if (id.endsWith('dist/client/client.mjs') || id.endsWith('dist/client/env.mjs')) {
              return code.replace('__HMR_HOSTNAME__', JSON.stringify('quvel.127.0.0.1.nip.io'));
            }

            return code;
          },
        },
      ],
    },
    framework: {
      config: {},
      iconSet: 'eva-icons',
      plugins: ['Cookies', 'Notify', 'LocalStorage', 'Meta', 'Loading'],
    },
    animations: ['fadeIn', 'fadeOut'],
    capacitor: {
      hideSplashscreen: true,
    },
  };
});
