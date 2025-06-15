/**
 * Centralized API configuration for TenantAdmin module
 * 
 * This file provides a single source of truth for API configuration
 * and eliminates duplication across service classes.
 */

/**
 * Get the API base URL from environment or use default fallback
 */
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/tenant/admin/api";

/**
 * Get the application base URL from environment or use default fallback  
 */
export const APP_BASE_URL = import.meta.env.VITE_BASE_URL || "/tenant/admin";

/**
 * API configuration object for future extensibility
 */
export const apiConfig = {
    baseUrl: API_BASE_URL,
    timeout: 10000,
    retries: 3,
} as const;