// Tenant related types

export interface Tenant {
    id: number;
    public_id: string;
    name: string;
    domain: string;
    parent_id: number | null;
    tier?: string;
    config: {
        tier?: string;
        domain?: string;
        app_url?: string;
        app_name?: string;
        db_host?: string;
        db_port?: string;
        db_database?: string;
        db_username?: string;
        db_password?: string;
        db_connection?: string;
        mail_mailer?: string;
        mail_host?: string;
        mail_port?: string;
        mail_username?: string;
        mail_password?: string;
        mail_encryption?: string;
        mail_from_address?: string;
        mail_from_name?: string;
        redis_host?: string;
        redis_port?: string;
        redis_password?: string;
        cache_store?: string;
        cache_prefix?: string;
        session_driver?: string;
        session_cookie?: string;
        session_domain?: string;
        session_lifetime?: number;
        frontend_url?: string;
        internal_api_url?: string;
        capacitor_scheme?: string;
        pusher_app_id?: string;
        pusher_app_key?: string;
        pusher_app_secret?: string;
        pusher_app_cluster?: string;
        recaptcha_site_key?: string;
        recaptcha_secret_key?: string;
        socialite_providers?: string[];
        status?: 'active' | 'inactive' | 'suspended';
        [key: string]: any;
    } | null;
    visibility?: {
        [key: string]: 'public' | 'protected' | 'private';
    };
    created_at: string;
    updated_at: string;
}

export interface TenantListResponse {
    data: Tenant[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface TenantCreateRequest {
    name: string;
    domain: string;
    database_name?: string;
    parent_id?: number;
    config?: {
        status?: 'active' | 'inactive' | 'suspended';
        [key: string]: any;
    };
}

export interface TenantUpdateRequest {
    name?: string;
    domain?: string;
    tier?: string;
    config?: {
        [key: string]: any;
    };
    visibility?: {
        [key: string]: 'public' | 'protected' | 'private';
    };
}

