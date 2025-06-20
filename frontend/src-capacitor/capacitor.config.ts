import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: process.env.APP_ID || 'quvel.irv.codes',
  appName: process.env.VITE_APP_NAME || 'QuVel Kit',
  webDir: 'www',
  server: {
    url: `https://${process.env.DEV_HOST || 'quvel.127.0.0.1.nip.io'}:${process.env.CAPACITOR_DEV_PORT || '3002'}`,
    cleartext: false
  }
};

export default config;