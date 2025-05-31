import { BaseApiService } from "./BaseApiService";
import type { ApiError } from "../types/api";
import type {
    Tenant,
    TenantListResponse,
    TenantCreateRequest,
    TenantUpdateRequest,
} from "../types/tenant";

/**
 * Service for handling TenantAdmin tenant operations.
 * Extends BaseApiService to inherit common HTTP methods and error handling.
 */
export class TenantService extends BaseApiService {
    constructor() {
        super("/admin/tenants/api");
    }

    /**
     * Get paginated list of tenants
     * @param page Page number
     * @param perPage Items per page
     * @returns Promise with tenant list
     */
    async list(
        page: number = 1,
        perPage: number = 10
    ): Promise<TenantListResponse> {
        return await this.get<TenantListResponse>(
            `/api/tenants?page=${page}&per_page=${perPage}`
        );
    }

    /**
     * Get single tenant by ID
     * @param id Tenant ID
     * @returns Promise with tenant data
     */
    async getById(id: number): Promise<Tenant> {
        return await this.get<Tenant>(`/api/tenants/${id}`);
    }

    /**
     * Create new tenant
     * @param data Tenant creation data
     * @returns Promise with created tenant
     */
    async create(data: TenantCreateRequest): Promise<Tenant> {
        return await this.post<Tenant>("/api/tenants", data);
    }

    /**
     * Update existing tenant
     * @param id Tenant ID
     * @param data Update data
     * @returns Promise with updated tenant
     */
    async update(id: number, data: TenantUpdateRequest): Promise<Tenant> {
        return await this.put<Tenant>(`/api/tenants/${id}`, data);
    }

    /**
     * Delete tenant
     * @param id Tenant ID
     * @returns Promise with success response
     */
    async deleteTenant(id: number): Promise<void> {
        return await this.delete<void>(`/api/tenants/${id}`);
    }

    /**
     * Search tenants
     * @param query Search query
     * @returns Promise with search results
     */
    async search(query: string): Promise<TenantListResponse> {
        return await this.get<TenantListResponse>(
            `/api/tenants/search?q=${encodeURIComponent(query)}`
        );
    }
}
