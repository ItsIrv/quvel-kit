/** Merges two objects recursively, handling nested objects. Works well enough for our cause of QuasarConf */
export function deepMerge<T extends Record<string, unknown>>(target: T, source: Partial<T>): T {
  for (const key in source) {
    if (
      Object.prototype.hasOwnProperty.call(source, key) &&
      typeof source[key] === 'object' &&
      source[key] !== null &&
      !Array.isArray(source[key])
    ) {
      if (!target[key] || typeof target[key] !== 'object') {
        target[key] = {} as T[Extract<keyof T, string>];
      }

      target[key] = deepMerge(
        target[key] as Record<string, unknown>,
        source[key] as Record<string, unknown>,
      ) as T[Extract<keyof T, string>];
    } else {
      target[key] = source[key] as T[Extract<keyof T, string>];
    }
  }
  return target;
}

/** Returns whether the app is running locally on your machine. */
export function isLocal(): boolean {
  return process.env.LOCAL === '1';
}

/** Helper for changing the certs path */
export function getCerts(): { key: string; cert: string; ca: string } {
  const certsDir = isLocal() ? '../docker/certs' : '/certs';

  return {
    key: `${certsDir}/selfsigned.key`,
    cert: `${certsDir}/selfsigned.crt`,
    ca: `${certsDir}/ca.pem`,
  };
}

/** Configuration helper for environment variables */
export const config = {
  /** Get environment variable with fallback */
  get(key: string, defaultValue: string): string {
    return process.env[key] || defaultValue;
  },

  /** Get numeric environment variable with fallback */
  getNumber(key: string, defaultValue: number): number {
    const value = process.env[key];
    const parsed = value ? parseInt(value, 10) : NaN;
    return isNaN(parsed) ? defaultValue : parsed;
  },

  /** Get boolean environment variable with fallback */
  getBoolean(key: string, defaultValue: boolean): boolean {
    const value = process.env[key];
    if (value === undefined) return defaultValue;
    return value === '1' || value === 'true' || value === 'yes';
  },

  /** Get array from comma-separated environment variable */
  getArray(key: string, defaultValue: string[] = []): string[] {
    const value = process.env[key];
    if (!value) return defaultValue;
    return value.split(',').map(item => item.trim()).filter(Boolean);
  },

  /** Infrastructure configuration */
  infra: {
    /** Get host based on environment */
    getHost(type: 'dev' | 'tenant' | 'prod' = 'dev'): string {
      if (!isLocal()) return config.get('PROD_HOST', '0.0.0.0');
      
      switch (type) {
        case 'tenant':
          return config.get('DEV_TENANT_HOST', 'cap-tenant.quvel.127.0.0.1.nip.io');
        case 'prod':
          return config.get('PROD_HOST', '0.0.0.0');
        default:
          return config.get('DEV_HOST', 'quvel.127.0.0.1.nip.io');
      }
    },

    /** Get port based on mode and environment */
    getPort(mode: 'ssr' | 'spa' | 'capacitor' | 'electron' | 'pwa', type: 'dev' | 'vite' | 'prod' = 'dev'): number {
      const key = type === 'vite' 
        ? `${mode.toUpperCase()}_VITE_PORT`
        : type === 'prod'
        ? `${mode.toUpperCase()}_PROD_PORT`
        : `${mode.toUpperCase()}_DEV_PORT`;

      const defaults: Record<string, number> = {
        SSR_DEV_PORT: 3000,
        SPA_DEV_PORT: 3001,
        CAPACITOR_DEV_PORT: 3002,
        ELECTRON_DEV_PORT: 3003,
        PWA_DEV_PORT: 3004,
        SSR_VITE_PORT: 9001,
        SPA_VITE_PORT: 9002,
        PWA_VITE_PORT: 9003,
        SSR_PROD_PORT: 3000,
      };

      return config.getNumber(key, defaults[key] || 3000);
    },

    /** Get allowed hosts */
    getAllowedHosts(): string[] {
      return config.getArray('ALLOWED_HOSTS', ['quvel.127.0.0.1.nip.io']);
    },

    /** Get HMR configuration */
    getHMR(): { hostname: string; clientPort: number } {
      return {
        hostname: config.get('HMR_HOSTNAME', 'quvel.127.0.0.1.nip.io'),
        clientPort: config.getNumber('HMR_CLIENT_PORT', 443),
      };
    },
  },

  /** App configuration */
  app: {
    getId(): string {
      return config.get('APP_ID', 'quvel.irv.codes');
    },

    getName(): string {
      return config.get('VITE_APP_NAME', 'QuVel Kit');
    },

    getShortName(): string {
      return config.get('APP_SHORT_NAME', 'QuVel');
    },
  },
};
