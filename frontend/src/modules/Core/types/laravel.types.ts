import { AxiosError } from 'axios';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

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
 *  Interface defining the structure of length-aware metadata.
 */
export interface LengthAwareMeta {
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
 * Interface defining the structure of simple metadata.
 */
export interface SimpleMeta {
  current_page: number;
  from: number | null;
  path: string;
  per_page: number;
  to: number | null;
}

/**
 * Interface defining the structure of cursor metadata.
 */
export interface CursorMeta {
  path: string;
  per_page: number;
  next_cursor: string | null;
  prev_cursor: string | null;
}

/**
 * Generic type for a LengthAwarePaginator response.
 */
export interface LengthAwarePaginatorResponse<Model> {
  data: Model[];
  links: PaginationLinks;
  meta: LengthAwareMeta;
}

/**
 * Generic type for a SimplePaginator response.
 */
export interface SimplePaginatorResponse<Model> {
  data: Model[];
  links: PaginationLinks;
  meta: SimpleMeta;
}

/**
 * Generic type for a CursorPaginator response.
 */
export interface CursorPaginatorResponse<Model> {
  data: Model[];
  links: PaginationLinks;
  meta: CursorMeta;
}

/**
 * Interface defining the structure of a LengthAwareRequest.
 */
export interface PaginationRequest {
  filter?: Record<string, string>;
  sort?: string;
  per_page?: number;
  page?: number;
}

/**
 * Interface defining the structure of a paginated state.
 */
export interface BasePaginationState<Model> {
  data: Model[];
  isLoadingMore: boolean;
  hasMore: boolean;
  currentPage: number;
}

/**
 * Interface for length-aware pagination
 */
export interface LengthAwareState<T> extends BasePaginationState<T> {
  meta: LengthAwareMeta;
  links: PaginationLinks;
}

/**
 * Interface for simple pagination
 */
export interface SimpleState<T> extends BasePaginationState<T> {
  meta: SimpleMeta;
  links: PaginationLinks;
}

/**
 * Interface for cursor pagination
 */
export interface CursorState<T> extends BasePaginationState<T> {
  meta: CursorMeta;
  links: PaginationLinks;
}

export type PaginatedModuleState<T> = LengthAwareState<T> | SimpleState<T> | CursorState<T>;

/**
 *  Store context for scoped pagination
 */
export type StoreContext<Prefix extends string, State> = {
  [key in Prefix]: State;
} & {
  $container: ServiceContainer;
} & PaginationActions<Prefix, State>;

/**
 *  Fetcher for scoped pagination
 */
export type Fetcher<Prefix extends string, Response, State> = (
  this: StoreContext<Prefix, State>,
  options: PaginationRequest,
  clearPrevious?: boolean,
) => Promise<Response | false>;

/**
 * Options for generating scoped pagination actions and getters
 */
export interface PaginationOptions<Prefix extends string, Response, State> {
  stateKey: Prefix;
  fetcher: Fetcher<Prefix, Response, State>;
}

/**
 * Keys of scoped pagination actions
 */
export type PaginationActionKeys<Prefix extends string> =
  | `${Prefix}Fetch`
  | `${Prefix}Next`
  | `${Prefix}Previous`
  | `${Prefix}Reload`;

/**
 * Generates scoped pagination actions.
 */
export type PaginationActions<Prefix extends string, State> = {
  [K in PaginationActionKeys<Prefix>]: (
    this: StoreContext<Prefix, State>,
    ...args: K extends `${Prefix}Fetch`
      ? [options?: PaginationRequest, clearPrevious?: boolean]
      : []
  ) => Promise<void>;
};

/**
 *  Generates scoped pagination getters using the current store context.
 */
export type PaginationGetters<
  Key extends string,
  Model,
  State extends PaginatedModuleState<Model>,
> = {
  [K in `get${Capitalize<Key>}`]: (state: Record<Key, State>) => Model[];
} & {
  [K in `has${Capitalize<Key>}`]: (state: Record<Key, State>) => boolean;
};
