import { register } from 'register-service-worker';

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

/**
 * Store tenant config in localStorage for PWA offline use.
 */
function storeTenantConfig(context: string): void {
  try {
    // Check if we're in browser environment
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
      return;
    }

    // Check if tenant config is available
    if (!window.__TENANT_CONFIG__) {
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

    const configData = JSON.stringify({
      config: window.__TENANT_CONFIG__,
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
    storeTenantConfig('registration backup');
  },

  cached(/* registration */) {
    console.log('QuVel Kit content has been cached for offline use.');
    
    // Store tenant config for offline PWA use
    storeTenantConfig('cache');
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
