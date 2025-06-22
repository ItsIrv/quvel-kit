import { register } from 'register-service-worker';
import type { AppConfig } from 'src/modules/Core/types/tenant.types';

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

/**
 * Fetch app config from public API for standalone PWA mode.
 */
async function fetchAppConfigForPWA(): Promise<AppConfig | null> {
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
    console.warn('QuVel Kit PWA: Failed to fetch app config:', error);
    return null;
  }
}

/**
 * Store app config in localStorage for PWA offline use.
 * For standalone PWA mode, this will fetch config if not available.
 */
async function storeAppConfig(context: string): Promise<void> {
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

    let appConfig = window.__APP_CONFIG__;

    // If no window config available (standalone PWA mode), fetch it
    if (!appConfig) {
      console.log('QuVel Kit PWA: No window config found, fetching from API...');
      appConfig = await fetchAppConfigForPWA();

      if (appConfig) {
        // Set it on window for other parts of the app to use
        window.__APP_CONFIG__ = appConfig;
      }
    }

    if (!appConfig) {
      console.warn('QuVel Kit PWA: No app config available to store');
      return;
    }

    const configData = JSON.stringify({
      config: appConfig,
      domain: domain,
      cachedAt: new Date().toISOString(),
    });

    localStorage.setItem(configKey, configData);
    console.log(`QuVel Kit: Stored app config for ${domain} (${context})`);
  } catch (error) {
    console.warn(`QuVel Kit: Failed to store app config during ${context}:`, error);
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

    // Store app config on registration as backup
    void storeAppConfig('registration backup');
  },

  cached(/* registration */) {
    console.log('QuVel Kit content has been cached for offline use.');

    // Store app config for offline PWA use
    void storeAppConfig('cache');
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
