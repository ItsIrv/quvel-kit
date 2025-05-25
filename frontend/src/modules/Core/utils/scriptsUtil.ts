/**
 * Dynamically loads an external script into the document.
 *
 * @param scriptId - Unique ID for the script element.
 * @param src - URL of the script to load.
 * @param onLoad - Optional callback triggered after the script loads.
 */
export function loadScript(scriptId: string, src: string, onLoad?: () => unknown): void {
  if (!document.getElementById(scriptId)) {
    const script = document.createElement('script');

    script.id = scriptId;
    script.src = src;
    script.async = true;
    script.defer = true;

    if (onLoad) {
      script.onload = onLoad;
    }

    document.head.appendChild(script);
  } else if (onLoad) {
    onLoad();
  }
}

/**
 * Removes a script from the DOM by its ID.
 *
 * @param scriptId - Unique ID of the script element to remove.
 */
export function unloadScript(scriptId: string): void {
  const script = document.getElementById(scriptId);

  if (script) {
    script.remove();
  }
}
