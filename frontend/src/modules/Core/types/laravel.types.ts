import { AxiosError } from 'axios';

/**
 * The generic error bag structure.
 * - Always a `Map<string, string>` for predictability.
 */
export type ErrorBag = Map<string, string>;

/**
 * Defines the Laravel error response structure.
 */
export type LaravelErrorResponse = AxiosError<{
  message?: string;
  errors?: Record<string, string | string[]>;
}>;

/**
 * Interface defining the structure of pagination links.
 */
export interface PaginationLinks {
  first: string | null;
  last: string | null;
  prev: string | null;
  next: string | null;
}

/**
 *  Interface defining the structure of pagination metadata.
 */
export interface PaginationMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  path: string;
  per_page: number;
  to: number | null;
  total: number;
}

/**
 * Generic type for a LengthAwarePaginator response.
 */
export interface LengthAwarePaginatorResponse<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

/**
 * Generic type for a SimplePaginator response.
 */
export interface SimplePaginatorResponse<T> {
  data: T[];
  current_page: number;
  per_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
  path: string;
}

/**
 * Generic type for a CursorPaginator response.
 */
export interface CursorPaginatorResponse<T> {
  data: T[];
  path: string;
  per_page: number;
  next_page_url: string | null;
  prev_page_url: string | null;
  cursor: {
    prev: string | null;
    next: string | null;
  };
}

/**
 * Interface defining the structure of a LengthAwareRequest.
 */
export interface LengthAwareRequest {
  filter?: Record<string, string>;
  sort?: string;
  per_page?: number;
  page?: number;
}

export interface PaginatedModule<T, B extends PaginatedModuleState<T> = PaginatedModuleState<T>> {
  state: B;
  fetch: (options?: LengthAwareRequest, clearPrevious?: boolean) => Promise<void>;
  reload: () => Promise<void>;
  next: () => Promise<void>;
  previous: () => Promise<void>;
}

export type PaginationType = 'length-aware' | 'simple' | 'cursor';

export interface BasePaginationState<T> {
  type: string;
  data: T[];
  isLoadingMore: boolean;
  hasMore: boolean;
  currentPage: number;
}

export interface LengthAwareState<T> extends BasePaginationState<T> {
  type: 'length-aware';
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface SimpleState<T> extends BasePaginationState<T> {
  type: 'simple';
  nextPageUrl: string | null;
  prevPageUrl: string | null;
  path: string;
}

export interface CursorState<T> extends BasePaginationState<T> {
  type: 'cursor';
  nextPageUrl: string | null;
  prevPageUrl: string | null;
  cursor: {
    next: string | null;
    prev: string | null;
  };
}

export type PaginatedModuleState<T> = LengthAwareState<T> | SimpleState<T> | CursorState<T>;
