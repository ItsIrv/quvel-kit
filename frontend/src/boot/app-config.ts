import { boot } from 'quasar/wrappers';
import { fetchPublicAppConfig } from 'src/modules/Core/utils/configUtil';
import { injectTenantAssets } from 'src/modules/Core/utils/assetUtil';

/**
 * App Config Boot File
 *
 * Handles async application configuration loading for non-SSR modes.
 * Only runs when:
 * - Not in SSR mode with SSR_MULTI_TENANT enabled
 * - No window.__APP_CONFIG__ available
 * - Public config API is enabled
 */
export default boot(async ({ ssrContext }) => {
  // Skip if in SSR mode with multi-tenant enabled
  if (ssrContext || import.meta.env.SSR_MULTI_TENANT === 'true') {
    return;
  }

  // Skip if app config already available
  if (typeof window !== 'undefined' && window.__APP_CONFIG__) {
    return;
  }

  // Skip if public config API not enabled
  const publicConfigEnabled = import.meta.env.VITE_PUBLIC_CONFIG_ENABLED === 'true';
  if (!publicConfigEnabled) {
    return;
  }

  try {
    // Fetch app config from public API
    const config = await fetchPublicAppConfig();

    if (config) {
      // Set global config for other services to use
      if (typeof window !== 'undefined') {
        window.__APP_CONFIG__ = config;
      }

      // Inject app assets if available
      if (config.assets) {
        injectTenantAssets(config.assets);
      }

      // Store in localStorage for PWA caching
      try {
        const domain = window.location.hostname;
        const configKey = `quvel_tenant_config_${domain}`;
        const configData = JSON.stringify({
          config: config,
          domain: domain,
          cachedAt: new Date().toISOString(),
        });

        localStorage.setItem(configKey, configData);
      } catch {
        // Silent fail for storage errors
      }
    }
  } catch {
    // Silent fail for config loading errors
  }
});
