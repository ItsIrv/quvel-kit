import axios from "axios";
import type {
    AxiosInstance,
    AxiosRequestConfig,
    AxiosResponse,
    AxiosError,
} from "axios";
import type { ApiError } from "../types/api";

/**
 * Base API Service class with error handling, CSRF token management, and common HTTP methods.
 * Implements enterprise patterns for API communication.
 */
export class BaseApiService {
    protected api: AxiosInstance;
    private abortControllers: Map<string, AbortController> = new Map();
    private requestCounter = 0;

    constructor(baseURL: string = "") {
        this.api = axios.create({
            baseURL,
            withCredentials: true,
            withXSRFToken: true,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        this.setupInterceptors();
        this.setupCsrfToken();
    }

    /**
     * Set up CSRF token from meta tag
     */
    private setupCsrfToken(): void {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        if (token) {
            this.api.defaults.headers.common["X-CSRF-TOKEN"] = token;
        }
    }

    /**
     * Set up request and response interceptors
     */
    private setupInterceptors(): void {
        // Request interceptor
        this.api.interceptors.request.use(
            (config) => {
                // Add timestamp for response time tracking
                config.metadata = { startTime: Date.now() };
                return config;
            },
            (error) => {
                return Promise.reject(this.normalizeError(error));
            }
        );

        // Response interceptor
        this.api.interceptors.response.use(
            (response) => {
                return response;
            },
            (error) => {
                return Promise.reject(this.normalizeError(error));
            }
        );
    }

    /**
     * Normalize error responses
     */
    protected normalizeError(error: AxiosError): ApiError {
        if (error.response) {
            // The request was made and the server responded with a status code
            // that falls out of the range of 2xx
            const response = error.response as AxiosResponse<any>;
            return {
                message:
                    response.data?.message ||
                    error.message ||
                    "An error occurred",
                errors: response.data?.errors,
                status: response.status,
            };
        } else if (error.request) {
            // The request was made but no response was received
            return {
                message:
                    "No response from server. Please check your connection.",
                status: 0,
            };
        } else {
            // Something happened in setting up the request
            return {
                message: error.message || "An error occurred",
                status: 0,
            };
        }
    }

    /**
     * Create a unique request ID for tracking abort controllers
     */
    private createRequestId(url: string, method: string): string {
        this.requestCounter += 1;
        return `${method}:${url}:${this.requestCounter}`;
    }

    /**
     * Create a cancellable request configuration
     */
    private createCancellableRequest(
        requestId: string,
        config?: AxiosRequestConfig
    ): AxiosRequestConfig {
        // Clean up any existing controller for this request ID
        this.cancelRequest(requestId);

        // Create a new abort controller
        const controller = new AbortController();
        this.abortControllers.set(requestId, controller);

        // Merge the signal with the existing config
        return {
            ...config,
            signal: controller.signal,
        };
    }

    /**
     * Cancel a specific request by ID
     */
    public cancelRequest(requestId: string): boolean {
        const controller = this.abortControllers.get(requestId);
        if (controller) {
            controller.abort();
            this.abortControllers.delete(requestId);
            return true;
        }
        return false;
    }

    /**
     * Cancel all pending requests
     */
    public cancelAllRequests(): number {
        let count = 0;
        this.abortControllers.forEach((controller) => {
            controller.abort();
            count++;
        });
        this.abortControllers.clear();
        return count;
    }

    /**
     * GET request
     */
    async get<T>(
        url: string,
        config?: AxiosRequestConfig,
        requestId?: string
    ): Promise<T> {
        const id = requestId || this.createRequestId(url, "GET");
        const cancellableConfig = this.createCancellableRequest(id, config);

        try {
            const response = await this.api.get<T>(url, cancellableConfig);
            this.abortControllers.delete(id);
            return response.data;
        } catch (error) {
            this.abortControllers.delete(id);
            throw error;
        }
    }

    /**
     * POST request
     */
    async post<T>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig,
        requestId?: string
    ): Promise<T> {
        const id = requestId || this.createRequestId(url, "POST");
        const cancellableConfig = this.createCancellableRequest(id, config);

        try {
            const response = await this.api.post<T>(
                url,
                data,
                cancellableConfig
            );
            this.abortControllers.delete(id);
            return response.data;
        } catch (error) {
            this.abortControllers.delete(id);
            throw error;
        }
    }

    /**
     * PUT request
     */
    async put<T>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig,
        requestId?: string
    ): Promise<T> {
        const id = requestId || this.createRequestId(url, "PUT");
        const cancellableConfig = this.createCancellableRequest(id, config);

        try {
            const response = await this.api.put<T>(
                url,
                data,
                cancellableConfig
            );
            this.abortControllers.delete(id);
            return response.data;
        } catch (error) {
            this.abortControllers.delete(id);
            throw error;
        }
    }

    /**
     * DELETE request
     */
    async delete<T>(
        url: string,
        config?: AxiosRequestConfig,
        requestId?: string
    ): Promise<T> {
        const id = requestId || this.createRequestId(url, "DELETE");
        const cancellableConfig = this.createCancellableRequest(id, config);

        try {
            const response = await this.api.delete<T>(url, cancellableConfig);
            this.abortControllers.delete(id);
            return response.data;
        } catch (error) {
            this.abortControllers.delete(id);
            throw error;
        }
    }

    /**
     * PATCH request
     */
    async patch<T>(
        url: string,
        data?: any,
        config?: AxiosRequestConfig,
        requestId?: string
    ): Promise<T> {
        const id = requestId || this.createRequestId(url, "PATCH");
        const cancellableConfig = this.createCancellableRequest(id, config);

        try {
            const response = await this.api.patch<T>(
                url,
                data,
                cancellableConfig
            );
            this.abortControllers.delete(id);
            return response.data;
        } catch (error) {
            this.abortControllers.delete(id);
            throw error;
        }
    }
}
