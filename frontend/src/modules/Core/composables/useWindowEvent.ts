import { onMounted, onUnmounted } from 'vue';

/**
 * @composable useWindowEvent
 *
 * @description
 * A Vue 3 composable for safely adding and removing window event listeners.
 * Ensures that event listeners are only added on the client-side and are
 * automatically cleaned up when the component is unmounted.
 *
 * @param {string} eventType - The type of event to listen for (e.g., 'scroll', 'resize').
 * @param {() => void} handler - The function to call when the event is triggered.
 * @param {boolean | AddEventListenerOptions} [options] - Optional event listener options.
 */
export function useWindowEvent(
  eventType: string,
  handler: () => void,
  options?: boolean | AddEventListenerOptions,
): void {
  /**
   * Adds the event listener if on the client side.
   */
  onMounted(() => {
    if (typeof window !== 'undefined') {
      window.addEventListener(eventType, handler, options);
    }
  });

  /**
   * Removes the event listener on unmount if on the client side.
   */
  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener(eventType, handler, options);
    }
  });
}
