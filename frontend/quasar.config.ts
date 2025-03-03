import { defineConfig } from '#q-app/wrappers';
import { deepMerge } from './config/utils';
import common from './config/common';
import ssr from './config/ssr';
import spa from './config/spa';
import capacitor from './config/capacitor';
import electron from './config/electron';

/**
 * Gets the common config and the config for the current mode
 */
export default defineConfig(async (ctx) => {
  const commonConfig = await common(ctx);
  let modeConfig = {};

  switch (ctx.modeName) {
    case 'spa':
      modeConfig = await spa(ctx);
      break;
    case 'ssr':
      modeConfig = await ssr(ctx);
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
