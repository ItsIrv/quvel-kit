import { BaseApiService } from "./BaseApiService";
import { API_BASE_URL } from "../config/api";
import type { ApiError } from "../types/api";
import type { LoginRequest, LoginResponse, User } from "../types/auth";

/**
 * Service for handling TenantAdmin authentication operations.
 * Extends BaseApiService to inherit common HTTP methods and error handling.
 */
export class AuthService extends BaseApiService {
    constructor() {
        super(API_BASE_URL);
    }

    /**
     * Authenticate user with credentials
     * @param data Login form data
     * @returns Promise with login result
     */
    async login(data: LoginRequest): Promise<LoginResponse> {
        try {
            return await this.post<LoginResponse>("/login", data);
        } catch (error) {
            const apiError = error as ApiError;

            // Handle validation errors specifically
            if (apiError.status === 422) {
                throw {
                    ...apiError,
                    message: "Invalid credentials. Please check your input.",
                };
            }

            // Handle unauthorized
            if (apiError.status === 401) {
                throw {
                    ...apiError,
                    message: "Invalid username or password.",
                };
            }

            // Handle other errors
            throw {
                ...apiError,
                message: apiError.message || "Login failed. Please try again.",
            };
        }
    }

    /**
     * Logout the current user
     * @returns Promise with logout result
     */
    async logout(): Promise<{ success: boolean; message?: string }> {
        try {
            return await this.post<{ success: boolean; message?: string }>(
                "/logout"
            );
        } catch (error) {
            const apiError = error as ApiError;
            throw {
                ...apiError,
                message: apiError.message || "Logout failed.",
            };
        }
    }

    /**
     * Get current authenticated user
     * @returns Promise with user data
     */
    async getUser(): Promise<User> {
        try {
            return await this.get<User>("/user");
        } catch (error) {
            const apiError = error as ApiError;

            if (apiError.status === 401) {
                throw {
                    ...apiError,
                    message: "Not authenticated.",
                };
            }

            throw {
                ...apiError,
                message: apiError.message || "Failed to get user data.",
            };
        }
    }

    /**
     * Check if user is authenticated
     * @returns Promise resolving to true if authenticated, false otherwise
     */
    async isAuthenticated(): Promise<boolean> {
        try {
            await this.getUser();
            return true;
        } catch (error) {
            return false;
        }
    }
}
