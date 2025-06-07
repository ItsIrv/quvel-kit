import { register } from 'register-service-worker';
import { Notify } from 'quasar';

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

register(process.env.SERVICE_WORKER_FILE, {
  // The registrationOptions object will be passed as the second argument
  // to ServiceWorkerContainer.register()
  // https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register#Parameter

  // registrationOptions: { scope: './' },

  ready (/* registration */) {
    console.log('QuVel Kit PWA is ready and being served from cache.');
  },

  registered (/* registration */) {
    console.log('QuVel Kit service worker has been registered.');
  },

  cached (/* registration */) {
    console.log('QuVel Kit content has been cached for offline use.');
    Notify.create({
      message: 'App is ready for offline use',
      color: 'positive',
      icon: 'cloud_done',
      position: 'bottom'
    });
  },

  updatefound (/* registration */) {
    console.log('New QuVel Kit content is downloading...');
    Notify.create({
      message: 'Downloading app update...',
      color: 'info',
      icon: 'cloud_download',
      position: 'bottom'
    });
  },

  updated (/* registration */) {
    console.log('New QuVel Kit content is available; refresh to update.');
    Notify.create({
      message: 'App updated! Refresh to get the latest version.',
      color: 'positive',
      icon: 'refresh',
      position: 'bottom',
      timeout: 0,
      actions: [
        {
          label: 'Refresh',
          color: 'white',
          handler: () => {
            window.location.reload();
          }
        },
        {
          label: 'Dismiss',
          color: 'white'
        }
      ]
    });
  },

  offline () {
    console.log('No internet connection found. QuVel Kit is running in offline mode.');
    Notify.create({
      message: 'You are offline. Some features may be limited.',
      color: 'warning',
      icon: 'cloud_off',
      position: 'bottom'
    });
  },

  error (err) {
    console.error('Error during QuVel Kit service worker registration:', err);
    Notify.create({
      message: 'Service worker registration failed',
      color: 'negative',
      icon: 'error',
      position: 'bottom'
    });
  },
});
