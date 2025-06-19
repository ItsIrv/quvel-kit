// Authentication related types

export interface LoginRequest {
    username: string;
    password: string;
    remember?: boolean;
}

export interface LoginResponse {
    success: boolean;
    message?: string;
    redirect_url?: string;
    user?: User;
}

export interface User {
    id: number;
    username: string;
    created_at: string;
    updated_at: string;
}

export interface AuthState {
    user: User | null;
    isAuthenticated: boolean;
    isLoading: boolean;
}