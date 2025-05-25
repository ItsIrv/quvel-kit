import { useScript } from 'src/modules/Core/composables/useScript';

/**
 * Script constants
 */
const SCRIPT_ID = 'google_recaptcha';
const RECAPTCHA_KEY = import.meta.env.VITE_RECAPTCHA_KEY || '';
const RECAPTCHA_URL = `https://www.google.com/recaptcha/api.js?render=${RECAPTCHA_KEY}`;

/**
 * Composable for handling Google reCAPTCHA v3.
 */
export function useRecaptcha() {
  const { isLoaded, isLoading, error, load, unload } = useScript(SCRIPT_ID, RECAPTCHA_URL, {
    autoLoad: true,
    autoUnload: true,
    onUnload: () => {
      // Cleanup global grecaptcha reference
      if ('grecaptcha' in window) {
        delete (window as { grecaptcha?: unknown }).grecaptcha;
      }

      // Remove badge injected by reCAPTCHA v3
      const badge = document.querySelector('.grecaptcha-badge');
      if (badge?.parentNode) {
        badge.parentNode.removeChild(badge);
      }

      // Remove any iframes injected by Google (optional, aggressive)
      document.querySelectorAll('iframe[src*="recaptcha"]').forEach((iframe) => {
        iframe.remove();
      });

      // Remove any scripts injected by Google (optional, aggressive)
      document.querySelectorAll('script[src*="recaptcha"]').forEach((script) => {
        script.remove();
      });

      document.querySelectorAll('meta[http-equiv="origin-trial"]').forEach((meta) => {
        meta.remove();
      });
    },
  });

  /**
   * Executes a reCAPTCHA action and returns a token.
   */
  async function execute(action: string): Promise<string> {
    if (!isLoaded.value) {
      await load();
    }

    if (!window.grecaptcha) {
      throw new Error('reCAPTCHA not available on window');
    }

    return new Promise((resolve, reject) => {
      try {
        window.grecaptcha.ready(() => {
          window.grecaptcha.execute(RECAPTCHA_KEY, { action }).then(resolve).catch(reject);
        });
      } catch (err) {
        reject(err instanceof Error ? err : new Error('Failed to execute reCAPTCHA'));
      }
    });
  }

  return {
    isLoaded,
    isLoading,
    error,
    load,
    unload,
    execute,
  };
}

declare global {
  interface Window {
    grecaptcha: {
      ready: (callback: () => void) => void;
      execute: (siteKey: string, options: { action: string }) => Promise<string>;
      reset: () => void;
    };
  }
}
