// Re-export service types for easier imports
export type { ApiError, ApiResponse } from "./api";
export type {
    InstallationStatus,
    InstallationRequest,
    InstallationResponse,
} from "../services/InstallationService";
export type {
    LoginRequest,
    LoginResponse,
    User,
    AuthState,
} from "./auth";

// Common UI state types
export interface LoadingState {
    loading: boolean;
    checkingStatus?: boolean;
}

export interface ErrorState {
    errors: Record<string, string[]>;
    message: string;
    messageType: "success" | "error" | "warning" | "info";
}

// Form validation types
export interface FormValidation {
    isValid: boolean;
    fieldErrors: Record<string, string>;
}

// API response wrapper types
export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface SuccessResponse<T = any> {
    success: true;
    data?: T;
    message?: string;
}

export interface ErrorResponse {
    success: false;
    message: string;
    errors?: Record<string, string[]>;
}
