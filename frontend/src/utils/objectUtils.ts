/**
 * Resolves a value that could be:
 * - A primitive (returned directly).
 * - A synchronous function (executed and its result returned).
 * - An asynchronous function (awaited and its result returned).
 *
 * @param value - The value to resolve.
 * @returns A promise that resolves to the resolved value.
 */
export async function resolveValue<T>(
  value: T | (() => T) | (() => Promise<T>),
): Promise<T | undefined> {
  try {
    if (typeof value === 'function') {
      const result = (value as () => T | Promise<T>)();
      return result instanceof Promise ? await result : result;
    }
    return value;
  } catch (error) {
    console.error('Error resolving value:', error);
    return undefined;
  }
}

/**
 * Safely retrieves a nested value from an object using dot notation.
 *
 * @param obj - The object to retrieve the value from.
 * @param path - The path in dot notation (e.g., "a.b.c").
 * @returns The value at the specified path or undefined if not found.
 */
export function getSafe<T = unknown>(obj: unknown, path: string): T | undefined {
  try {
    if (path === '') return obj as T;

    return path.split('.').reduce((acc, key) => {
      if (acc !== null && typeof acc === 'object' && key in acc) {
        return (acc as Record<string, unknown>)[key];
      }
      throw new Error(`Property ${key} does not exist on ${JSON.stringify(acc)}`);
    }, obj) as T;
  } catch {
    return undefined;
  }
}
