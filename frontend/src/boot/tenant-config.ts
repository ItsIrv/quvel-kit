import { boot } from 'quasar/wrappers';
import { fetchPublicTenantConfig } from 'src/modules/Core/utils/configUtil';

/**
 * Tenant Config Boot File
 * 
 * Handles async tenant configuration loading for non-SSR modes.
 * Only runs when:
 * - Not in SSR mode
 * - No window.__TENANT_CONFIG__ available
 * - Public config URL is configured
 */
export default boot(async ({ ssrContext }) => {
  // Skip if in SSR mode
  if (ssrContext) {
    return;
  }

  // Skip if tenant config already available
  if (typeof window !== 'undefined' && window.__TENANT_CONFIG__) {
    return;
  }

  // Skip if public config URL not configured
  const publicConfigUrl = import.meta.env.VITE_PUBLIC_CONFIG_URL;
  if (!publicConfigUrl) {
    return;
  }

  console.log('QuVel Kit: Loading tenant config before app initialization...');

  try {
    // Fetch tenant config from public API
    const config = await fetchPublicTenantConfig();
    
    if (config) {
      // Set global config for other services to use
      if (typeof window !== 'undefined') {
        window.__TENANT_CONFIG__ = config;
        console.log('QuVel Kit: Tenant config loaded successfully');
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
        console.log(`QuVel Kit: Cached tenant config for ${domain}`);
      } catch (storageError) {
        console.warn('QuVel Kit: Failed to cache tenant config:', storageError);
      }
    } else {
      console.warn('QuVel Kit: Failed to load tenant config, using environment fallback');
    }
  } catch (error) {
    console.error('QuVel Kit: Error loading tenant config:', error);
  }
});