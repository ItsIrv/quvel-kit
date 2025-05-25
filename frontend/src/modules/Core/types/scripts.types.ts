/**
 * Interface for script loading options
 */
export interface ScriptOptions {
  /**
   * Auto-load the script on component mount
   */
  autoLoad?: boolean;

  /**
   * Auto-unload the script on component unmount
   */
  autoUnload?: boolean;

  /**
   * Callback function to execute after script is loaded
   */
  onLoad?: () => void;

  /**
   * Callback function to execute after script is unloaded
   */
  onUnload?: () => void;

  /**
   * Callback function to execute if script loading fails
   */
  onError?: (error: Error) => void;
}
