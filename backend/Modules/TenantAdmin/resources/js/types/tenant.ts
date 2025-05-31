// Tenant related types

export interface Tenant {
    id: number;
    public_id: string;
    name: string;
    domain: string;
    parent_id: number | null;
    config: {
        database_name?: string;
        status?: 'active' | 'inactive' | 'suspended';
        [key: string]: any;
    } | null;
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
    config?: {
        status?: 'active' | 'inactive' | 'suspended';
        [key: string]: any;
    };
}

