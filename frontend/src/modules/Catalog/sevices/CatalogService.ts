import { BootableService } from 'src/modules/Core/types/service.types';
import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import {
  LengthAwarePaginatorResponse,
  LengthAwareRequest,
} from 'src/modules/Core/types/laravel.types';

/**
 * Provides methods to fetch catalogs from the backend.
 */
export class CatalogService implements BootableService {
  private api: ApiService = {} as ApiService;

  register(container: ServiceContainer) {
    this.api = container.api;
  }

  /**
   * Fetches catalogs from the backend.
   */
  async fetchCatalogs(
    options: LengthAwareRequest = {},
  ): Promise<LengthAwarePaginatorResponse<CatalogItem>> {
    const { filter = {}, sort, per_page, page } = options;
    const params: Record<string, string | number> = {};

    // Laravel expects filter[search]=value style
    Object.entries(filter).forEach(([key, value]) => {
      params[`filter[${key}]`] = value;
    });

    if (sort) params.sort = sort;
    if (per_page) params.per_page = per_page;
    if (page) params.page = page;

    return await this.api.get<LengthAwarePaginatorResponse<CatalogItem>>('catalogs', { params });
  }
}
