import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  return {
    devServer: {
      allowedHosts: ['quvel.127.0.0.1.nip.io', 'cap-tenant.quvel.192.168.86.245.nip.io'],
      strictPort: true,
      port: 3002,
      host: 'quvel.127.0.0.1.nip.io',
      https: getCerts(),
      open: false,
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
