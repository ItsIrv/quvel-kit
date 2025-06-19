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
