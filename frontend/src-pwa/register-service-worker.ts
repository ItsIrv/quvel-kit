import { register } from 'register-service-worker';
import type { TenantConfig } from 'src/modules/Core/types/tenant.types';

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

/**
 * Fetch tenant config from public API for standalone PWA mode.
 */
async function fetchTenantConfigForPWA(): Promise<TenantConfig | null> {
  try {
    // Check if public config is enabled
    const publicConfigEnabled = import.meta.env.VITE_PUBLIC_CONFIG_ENABLED === 'true';
    if (!publicConfigEnabled) {
      return null;
    }

    // Construct URL from current host - no cross-domain requests allowed
    const protocol = window.location.protocol;
    const hostname = window.location.hostname;
    const port = window.location.port ? `:${window.location.port}` : '';
    const publicConfigUrl = `${protocol}//${hostname}${port}/api/tenant-info/public`;

    const response = await fetch(publicConfigUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      return null;
    }

    const data = await response.json();
    return data.data?.config || null;
  } catch (error) {
    console.warn('QuVel Kit PWA: Failed to fetch tenant config:', error);
    return null;
  }
}

/**
 * Store tenant config in localStorage for PWA offline use.
 * For standalone PWA mode, this will fetch config if not available.
 */
async function storeTenantConfig(context: string): Promise<void> {
  try {
    // Check if we're in browser environment
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
      return;
    }

    const domain = window.location.hostname;
    const configKey = `quvel_tenant_config_${domain}`;

    // For registration backup, don't overwrite existing config
    if (context === 'registration backup') {
      const existingConfig = localStorage.getItem(configKey);
      if (existingConfig) {
        return;
      }
    }

    let tenantConfig = window.__TENANT_CONFIG__;

    // If no window config available (standalone PWA mode), fetch it
    if (!tenantConfig) {
      console.log('QuVel Kit PWA: No window config found, fetching from API...');
      tenantConfig = await fetchTenantConfigForPWA();

      if (tenantConfig) {
        // Set it on window for other parts of the app to use
        window.__TENANT_CONFIG__ = tenantConfig;
      }
    }

    if (!tenantConfig) {
      console.warn('QuVel Kit PWA: No tenant config available to store');
      return;
    }

    const configData = JSON.stringify({
      config: tenantConfig,
      domain: domain,
      cachedAt: new Date().toISOString(),
    });

    localStorage.setItem(configKey, configData);
    console.log(`QuVel Kit: Stored tenant config for ${domain} (${context})`);
  } catch (error) {
    console.warn(`QuVel Kit: Failed to store tenant config during ${context}:`, error);
  }
}

register(process.env.SERVICE_WORKER_FILE, {
  // The registrationOptions object will be passed as the second argument
  // to ServiceWorkerContainer.register()
  // https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register#Parameter

  // registrationOptions: { scope: './' },

  ready(/* registration */) {
    console.log('QuVel Kit PWA is ready and being served from cache.');
  },

  registered(/* registration */) {
    console.log('QuVel Kit service worker has been registered.');

    // Store tenant config on registration as backup
    void storeTenantConfig('registration backup');
  },

  cached(/* registration */) {
    console.log('QuVel Kit content has been cached for offline use.');

    // Store tenant config for offline PWA use
    void storeTenantConfig('cache');
  },

  updatefound(/* registration */) {
    console.log('New QuVel Kit content is downloading...');
  },

  updated(/* registration */) {
    console.log('New QuVel Kit content is available; refresh to update.');
  },

  offline() {
    console.log('No internet connection found. QuVel Kit is running in offline mode.');
  },

  error(err) {
    console.error('Error during QuVel Kit service worker registration:', err);
  },
});
