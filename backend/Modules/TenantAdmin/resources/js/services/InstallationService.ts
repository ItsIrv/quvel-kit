import { BaseApiService } from "./BaseApiService";
import { API_BASE_URL } from "../config/api";
import type { ApiError } from "../types/api";
import type {
    InstallationStatus,
    InstallationRequest,
    InstallationResponse,
} from "../types/services";

/**
 * Service for handling TenantAdmin installation operations.
 * Extends BaseApiService to inherit common HTTP methods and error handling.
 */
export class InstallationService extends BaseApiService {
    constructor() {
        super(API_BASE_URL);
    }

    /**
     * Check the current installation status
     * @returns Promise with installation status information
     */
    async checkStatus(): Promise<InstallationStatus> {
        try {
            return await this.get<InstallationStatus>("/install/status");
        } catch (error) {
            // Re-throw with more context
            throw {
                ...(error as ApiError),
                message: "Failed to check installation status",
            };
        }
    }

    /**
     * Process the installation with provided credentials
     * @param data Installation form data
     * @returns Promise with installation result
     */
    async install(data: InstallationRequest): Promise<InstallationResponse> {
        try {
            return await this.post<InstallationResponse>("/install", data);
        } catch (error) {
            const apiError = error as ApiError;

            // Handle validation errors specifically
            if (apiError.status === 422) {
                throw {
                    ...apiError,
                    message: "Validation failed. Please check your input.",
                };
            }

            // Handle other errors
            throw {
                ...apiError,
                message:
                    apiError.message ||
                    "Installation failed. Please try again.",
            };
        }
    }

    /**
     * Verify if the system is already installed
     * @returns Promise resolving to true if installed, false otherwise
     */
    async isInstalled(): Promise<boolean> {
        try {
            const status = await this.checkStatus();
            return status.installed;
        } catch (error) {
            // If we can't check status, assume not installed
            console.error("Failed to verify installation status:", error);
            return false;
        }
    }

    /**
     * Get available installation methods based on system configuration
     * @returns Promise with available methods
     */
    async getAvailableMethods(): Promise<{
        env: boolean;
        database: boolean;
    }> {
        try {
            const status = await this.checkStatus();
            return {
                env: status.has_env_credentials,
                database: status.has_database_credentials,
            };
        } catch (error) {
            // Default to database method if status check fails
            return {
                env: false,
                database: true,
            };
        }
    }
}
