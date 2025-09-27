import { ref, onMounted } from 'vue';

/**
 * Composable that provides client-side detection
 *
 * @returns An object containing isClient state and related utilities
 */
export function useClient() {
  /**
   * Flag indicating if the code is running on the client-side
   */
  const isClient = ref(false);

  /**
   * Sets isClient to true when component is mounted (client-side only)
   */
  onMounted(() => {
    isClient.value = true;
  });

  return {
    isClient,
  };
}