import { BootableService } from 'src/modules/Core/types/service.types';
import type { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { ApiService } from 'src/modules/Core/services/ApiService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import {
  CursorPaginatorResponse,
  LengthAwarePaginatorResponse,
  PaginationRequest,
  SimplePaginatorResponse,
} from 'src/modules/Core/types/laravel.types';

/**
 * Provides methods to fetch catalogs from the backend.
 */
export class CatalogService implements BootableService {
  private api: ApiService = {} as ApiService;

  register({ api }: ServiceContainer) {
    this.api = api;
  }

  /**
   * Internal helper to build Laravel-style query params.
   */
  private buildParams({ filter = {}, sort, per_page, page }: PaginationRequest = {}): Record<
    string,
    string | number
  > {
    const params: Record<string, string | number> = {};

    Object.entries(filter).forEach(([key, value]) => {
      params[`filter[${key}]`] = value;
    });

    if (sort) params.sort = sort;
    if (per_page) params.per_page = per_page;
    if (page) params.page = page;

    return params;
  }

  /**
   * Internal generic fetcher for paginated responses.
   */
  private async fetch<T>(
    options: PaginationRequest = {},
  ): Promise<
    T extends 'length'
      ? LengthAwarePaginatorResponse<CatalogItem>
      : T extends 'simple'
        ? SimplePaginatorResponse<CatalogItem>
        : CursorPaginatorResponse<CatalogItem>
  > {
    const params = this.buildParams(options);
    return await this.api.get('catalogs', { params });
  }

  /**
   * Fetches length-aware paginated catalogs.
   */
  fetchCatalogs(
    options: PaginationRequest = {},
  ): Promise<LengthAwarePaginatorResponse<CatalogItem>> {
    return this.fetch<'length'>(options);
  }

  /**
   * Fetches simple paginated catalogs.
   */
  fetchCatalogsSimple(
    options: PaginationRequest = {},
  ): Promise<SimplePaginatorResponse<CatalogItem>> {
    return this.fetch<'simple'>(options);
  }

  /**
   * Fetches cursor paginated catalogs.
   */
  fetchCatalogsCursor(
    options: PaginationRequest = {},
  ): Promise<CursorPaginatorResponse<CatalogItem>> {
    return this.fetch<'cursor'>(options);
  }
}
