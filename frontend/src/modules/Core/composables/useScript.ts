import { ref, onBeforeUnmount } from 'vue';
import { loadScript, unloadScript } from 'src/modules/Core/utils/scriptsUtil';
import type { ScriptOptions } from 'src/modules/Core/types/scripts.types';

/**
 * Composable for dynamically loading external scripts.
 *
 * This composable provides a clean interface for loading, unloading, and managing
 * external scripts in Vue components. It handles script lifecycle, loading states,
 * and error handling.
 *
 * @param scriptId - Unique identifier for the script element
 * @param scriptUrl - URL of the script to load
 * @param options - Configuration options for script loading behavior
 * @returns Object containing script state and methods
 */
export function useScript(scriptId: string, scriptUrl: string, options: ScriptOptions = {}) {
  const isLoaded = ref(false);
  const isLoading = ref(false);
  const error = ref<Error | null>(null);

  /**
   * Loads the script if not already loaded.
   *
   * @returns Promise resolving when script is loaded
   */
  function load(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (isLoaded.value) {
        resolve();
        return;
      }

      if (isLoading.value) {
        const checkInterval = setInterval(() => {
          if (isLoaded.value) {
            clearInterval(checkInterval);
            resolve();
          }
          if (error.value) {
            clearInterval(checkInterval);
            reject(error.value);
          }
        }, 100);
        return;
      }

      isLoading.value = true;

      try {
        loadScript(scriptId, scriptUrl, () => {
          isLoaded.value = true;
          isLoading.value = false;

          if (options.onLoad) {
            options.onLoad();
          }

          resolve();
        });
      } catch (err) {
        isLoading.value = false;
        error.value = err instanceof Error ? err : new Error(`Failed to load script: ${scriptId}`);

        if (options.onError) {
          options.onError(error.value);
        }

        reject(error.value);
      }
    });
  }

  /**
   * Unloads the script and resets the state.
   */
  function unload(): void {
    unloadScript(scriptId);

    if (options.onUnload) {
      options.onUnload();
    }

    isLoaded.value = false;
    isLoading.value = false;
    error.value = null;
  }

  /**
   * Reloads the script by unloading and loading it again.
   *
   * @returns Promise resolving when script is reloaded
   */
  async function reload(): Promise<void> {
    unload();
    return load();
  }

  if (options.autoLoad) {
    void load();
  }

  if (options.autoUnload !== false) {
    onBeforeUnmount(() => {
      unload();
    });
  }

  return {
    isLoaded,
    isLoading,
    error,
    load,
    unload,
    reload,
  };
}
