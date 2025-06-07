import { defineConfig } from '#q-app/wrappers';
import { deepMerge } from './config/utils';
import common from './config/common';
import ssr from './config/ssr';
import spa from './config/spa';
import capacitor from './config/capacitor';
import electron from './config/electron';
import pwa from './config/pwa';

/**
 * Gets the common config and the config for the current mode
 */
export default defineConfig(async (ctx) => {
  // Prevent PWA mode when multi-tenancy is enabled
  if (ctx.modeName === 'pwa' && process.env.VITE_MULTI_TENANT === 'true') {
    console.error('\n❌ PWA mode is not compatible with multi-tenant setup (VITE_MULTI_TENANT=true)');
    console.error('Please use one of the following modes instead:');
    console.error('  - SSR mode: npm run dev:ssr');
    console.error('  - SPA mode: npm run dev\n');
    process.exit(1);
  }

  const commonConfig = await common(ctx);
  let modeConfig = {};

  switch (ctx.modeName) {
    case 'spa':
      modeConfig = await spa(ctx);
      break;
    case 'ssr':
      modeConfig = await ssr(ctx);
      break;
    case 'pwa':
      modeConfig = await pwa(ctx);
      break;
    case 'capacitor':
      modeConfig = await capacitor(ctx);
      break;
    case 'electron':
      modeConfig = await electron(ctx);
      break;
  }

  return deepMerge(commonConfig as Record<string, unknown>, modeConfig);
});
