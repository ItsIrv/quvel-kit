import { register } from 'register-service-worker';

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

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
  },

  cached(/* registration */) {
    console.log('QuVel Kit content has been cached for offline use.');
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
