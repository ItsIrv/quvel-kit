export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    status?: number;
}

export interface ApiResponse<T = any> {
    data: T;
    message?: string;
}
