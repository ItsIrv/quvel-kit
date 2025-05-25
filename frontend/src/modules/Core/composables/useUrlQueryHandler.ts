import { onMounted, ref } from 'vue';

/**
 * Options for the URL query handler
 */
export interface UrlQueryHandlerOptions<T = Record<string, string>> {
  /**
   * Parameters to extract from the URL query
   */
  params: string[];

  /**
   * Validation function to check if the extracted parameters meet certain criteria
   */
  validate?: (params: T) => boolean;

  /**
   * Whether to clean up the URL by removing the extracted parameters
   */
  cleanupUrl?: boolean;

  /**
   * Whether to run the handler only once
   */
  runOnce?: boolean;

  /**
   * Callback function to execute when the parameters are found and validated
   */
  onMatch?: (params: T) => void;
}

/**
 * A composable for handling URL query parameters.
 *
 * This composable provides a generic way to extract, validate, and act on URL query parameters.
 * It can be used for various purposes like handling authentication tokens, reset passwords,
 * invitation links, etc.
 *
 * @param options - Configuration options for the URL query handler
 * @returns An object containing the extracted parameters and a function to manually check the URL
 */
export function useUrlQueryHandler<T = Record<string, string>>(options: UrlQueryHandlerOptions<T>) {
  const { params, validate = () => true, cleanupUrl = true, runOnce = true, onMatch } = options;

  const hasRun = ref(false);
  const extractedParams = ref<T>({} as T);

  /**
   * Extracts and processes query parameters from the URL
   */
  function checkUrlParams(): boolean {
    if (runOnce && hasRun.value) {
      return false;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const extractedValues = {} as Record<string, string>;

    // Extract all requested parameters
    let allParamsFound = true;
    for (const param of params) {
      const value = urlParams.get(param);
      if (value) {
        extractedValues[param] = value;
      } else {
        allParamsFound = false;
      }
    }

    if (!allParamsFound) {
      return false;
    }

    // Validate the extracted parameters
    if (!validate(extractedValues as T)) {
      return false;
    }

    // Store the extracted parameters
    extractedParams.value = extractedValues as T;

    // Clean up the URL if requested
    if (cleanupUrl && window.history && window.history.replaceState) {
      // Create a new URL without the extracted parameters
      const newUrl = new URL(window.location.href);
      params.forEach((param) => {
        newUrl.searchParams.delete(param);
      });

      window.history.replaceState({}, document.title, newUrl.pathname + newUrl.search);
    }

    // Execute the callback if provided
    if (onMatch) {
      onMatch(extractedValues as T);
    }

    hasRun.value = true;
    return true;
  }

  // Check URL parameters on component mount
  onMounted(() => {
    checkUrlParams();
  });

  return {
    params: extractedParams,
    checkUrlParams,
  };
}
