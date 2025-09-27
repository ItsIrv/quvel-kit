/**
 * Module Utilities
 *
 * Helper functions for working with module resources and paths.
 */

/**
 * Generates the correct relative path for module resources.
 *
 * Quasar requires specific relative paths for boot files and CSS imports.
 * This helper ensures consistent path generation across all modules.
 *
 * @param moduleName - Name of the module (e.g., 'Core', 'Auth')
 * @param resourcePath - Path within the module (e.g., 'boot/setup', 'css/styles.scss')
 * @param basePath - Base path for modules (default: '../modules')
 * @returns Properly formatted relative path for Quasar
 *
 * @example
 * moduleResource('Core', 'boot/app-config')
 * // Returns: '../modules/Core/boot/app-config'
 *
 * moduleResource('Auth', 'css/auth.scss', '../src/modules')
 * // Returns: '../src/modules/Auth/css/auth.scss'
 */
export function moduleResource(
  moduleName: string,
  resourcePath: string,
  basePath: string = '../modules'
): string {
  return `${basePath}/${moduleName}/${resourcePath}`;
}

/**
 * Generates paths for multiple resources from the same module.
 *
 * @param moduleName - Name of the module
 * @param resourcePaths - Array of resource paths within the module
 * @param basePath - Base path for modules (default: '../modules')
 * @returns Array of properly formatted relative paths
 *
 * @example
 * moduleResources('Core', ['boot/container', 'boot/app-config'])
 * // Returns: ['../modules/Core/boot/container', '../modules/Core/boot/app-config']
 */
export function moduleResources(
  moduleName: string,
  resourcePaths: string[],
  basePath?: string
): string[] {
  return resourcePaths.map(path => moduleResource(moduleName, path, basePath));
}