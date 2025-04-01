/**
 * Generic type for a Laravel LengthAwarePaginator response.
 */
export interface LengthAwarePaginator<T> {
  data: T[];

  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };

  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}

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
 *  Interface defining the structure of pagination meta data.
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
 *
 */
export interface PaginatedResponse<T> {
  data: T[];
  links: PaginationLinks;
  meta: PaginationMeta;
}

export interface LengthAwareRequest {
  filter?: Record<string, string>;
  sort?: string;
  per_page?: number;
  page?: number;
}
