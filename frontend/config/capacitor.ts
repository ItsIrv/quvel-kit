import { defineConfig } from '#q-app/wrappers';
import { getCerts } from './utils';

export default defineConfig(() => {
  const isMultiTenant = process.env.SSR_MULTI_TENANT === 'true';

  return {
    boot: [
      // Add tenant config boot file for Capacitor mode only in multi-tenant setups
      ...(isMultiTenant ? ['tenant-config'] : []),
      'container',
      {
        server: false,
        path: 'pinia-hydrator',
      },
    ],
    devServer: {
      allowedHosts: ['quvel.127.0.0.1.nip.io', 'cap-tenant.quvel.192.168.86.21.nip.io'],
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
