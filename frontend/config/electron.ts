import { defineConfig } from '#q-app/wrappers';
import { getCerts, config } from './utils';

export default defineConfig(() => {
  return {
    devServer: {
      strictPort: true,
      port: config.infra.getPort('electron', 'dev'),
      host: config.infra.getHost('tenant'),
      https: getCerts(),
    },
  };
});
