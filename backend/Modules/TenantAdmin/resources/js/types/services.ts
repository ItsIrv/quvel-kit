export interface InstallationStatus {
    installed: boolean;
    method: string | null;
    has_env_credentials: boolean;
    has_database_credentials: boolean;
}

export interface InstallationRequest {
    username: string;
    password: string;
    password_confirmation: string;
    installation_method: "env" | "database";
}

export interface InstallationResponse {
    success: boolean;
    message?: string;
    redirect_url?: string;
}
