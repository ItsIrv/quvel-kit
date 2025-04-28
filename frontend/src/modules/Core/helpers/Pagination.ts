import { capitalize } from 'vue';
import {
  CursorPaginatorResponse,
  CursorState,
  LengthAwarePaginatorResponse,
  LengthAwareState,
  PaginatedModuleState,
  PaginationActionKeys,
  PaginationActions,
  PaginationGetters,
  PaginationOptions,
  PaginationRequest,
  SimplePaginatorResponse,
  SimpleState,
  StoreContext,
  Fetcher,
  CursorMeta,
  LengthAwareMeta,
  SimpleMeta,
} from 'src/modules/Core/types/laravel.types';

/* -----------------------------------
 * Utilities
 * ----------------------------------- */

/**
 * Checks if an object is a cursor paginator.
 */
export function isCursorPagination(meta: unknown): meta is CursorMeta {
  return !!meta && typeof meta === 'object' && 'next_cursor' in meta && 'prev_cursor' in meta;
}

/**
 * Checks if an object is a length-aware paginator.
 */
export function isLengthAwarePagination(meta: unknown): meta is LengthAwareMeta {
  return !!meta && typeof meta === 'object' && 'total' in meta && 'last_page' in meta;
}

/**
 * Checks if an object is a simple paginator
 */
export function isSimplePagination(meta: unknown): meta is SimpleMeta {
  return (
    !!meta &&
    typeof meta === 'object' &&
    'current_page' in meta &&
    !('total' in meta) &&
    !('next_cursor' in meta)
  );
}

/* -----------------------------------
 * Pagination Getters Factory
 * ----------------------------------- */
export function createPaginationGetters<
  Key extends string,
  Model,
  State extends PaginatedModuleState<Model>,
>(key: Key): PaginationGetters<Key, Model, State> {
  const capitalizedKey = capitalize(key);

  return {
    [`get${capitalizedKey}`]: (state: Record<Key, State>) => state[key].data,
    [`has${capitalizedKey}`]: (state: Record<Key, State>) => state[key].data.length > 0,
  } as PaginationGetters<Key, Model, State>;
}

/* -----------------------------------
 * Generic Pagination Action Factory
 * ----------------------------------- */
export function createPaginationActions<
  Key extends string,
  Response,
  State extends PaginatedModuleState<unknown>,
>(
  options: PaginationOptions<Key, Response, State> & {
    transform: (state: State, response: Response) => void;
  },
): PaginationActions<Key, Response> {
  const { stateKey, fetcher, transform } = options;

  const fetchKey = `${stateKey}Fetch` as PaginationActionKeys<Key>;
  const nextKey = `${stateKey}Next` as PaginationActionKeys<Key>;
  const prevKey = `${stateKey}Previous` as PaginationActionKeys<Key>;
  const reloadKey = `${stateKey}Reload` as PaginationActionKeys<Key>;

  return {
    async [fetchKey](this: StoreContext<Key, State>, options = {}, clearPrevious = true) {
      const state = this[stateKey];
      if (clearPrevious) state.data = [];
      state.isLoadingMore = true;

      try {
        const response = await fetcher.call(this, options);

        if (!response) {
          throw new Error('Failed to fetch data');
        }

        transform(state, response);
      } catch {
        if (clearPrevious) {
          state.data = [];
          if (state.meta) {
            state.meta = {
              current_page: 1,
              from: null,
              last_page: 1,
              links: [],
              path: '',
              per_page: 10,
              to: null,
              total: 0,
            };
          }
        }
      } finally {
        state.isLoadingMore = false;
      }
    },

    async [nextKey](this: StoreContext<Key, State>) {
      const state = this[stateKey];
      if (!state.hasMore || state.isLoadingMore) return;
      await (this[fetchKey] as Fetcher<Key, unknown, State>)(
        { page: state.currentPage + 1 },
        false,
      );
    },

    async [prevKey](this: StoreContext<Key, State>) {
      const state = this[stateKey];
      if (state.currentPage <= 1) return;
      await (this[fetchKey] as Fetcher<Key, unknown, State>)(
        { page: state.currentPage - 1 },
        false,
      );
    },

    async [reloadKey](this: StoreContext<Key, State>) {
      await (this[fetchKey] as Fetcher<Key, unknown, State>)({ page: 1 });
    },
  } as PaginationActions<Key, Response>;
}

/* -----------------------------------
 * Length-Aware Implementation
 * ----------------------------------- */
export function createLengthAwareState<T>(): LengthAwareState<T> {
  return {
    data: [],
    currentPage: 1,
    hasMore: true,
    isLoadingMore: false,
    meta: {
      current_page: 1,
      from: 1,
      last_page: 1,
      per_page: 10,
      to: 1,
      total: 1,
      links: [],
      path: '',
    },
    links: {
      first: null,
      last: null,
      prev: null,
      next: null,
    },
  };
}

export function createLengthAwareActions<Key extends string, Model = unknown>(
  options: PaginationOptions<Key, LengthAwarePaginatorResponse<Model>, LengthAwareState<Model>>,
): PaginationActions<Key, LengthAwarePaginatorResponse<Model>> {
  return createPaginationActions({
    ...options,
    transform: (state, res) => {
      state.data = res.data;
      state.meta = res.meta;
      state.links = res.links;
      state.currentPage = res.meta.current_page;
      state.hasMore = res.meta.current_page < res.meta.last_page;
    },
  });
}

export function createLengthAwareGetters<Key extends string, Model>(
  key: Key,
): PaginationGetters<Key, Model, LengthAwareState<Model>> {
  return createPaginationGetters(key);
}

/* -----------------------------------
 * Simple Pagination Implementation
 * ----------------------------------- */
export function createSimpleState<T>(): SimpleState<T> {
  return {
    data: [],
    currentPage: 1,
    hasMore: true,
    isLoadingMore: false,
    meta: {
      current_page: 1,
      from: null,
      path: '',
      per_page: 10,
      to: null,
    },
    links: {
      first: null,
      last: null,
      prev: null,
      next: null,
    },
  };
}

export function createSimpleActions<Key extends string, Model = unknown>(
  options: PaginationOptions<Key, SimplePaginatorResponse<Model>, SimpleState<Model>>,
): PaginationActions<Key, SimplePaginatorResponse<Model>> {
  return createPaginationActions({
    ...options,
    transform: (state, res) => {
      state.data = res.data;
      state.meta = res.meta;
      state.links = res.links;
      state.currentPage = res.meta.current_page;
      state.hasMore = true;
    },
  });
}

export function createSimpleGetters<Key extends string, Model>(
  key: Key,
): PaginationGetters<Key, Model, SimpleState<Model>> {
  return createPaginationGetters(key);
}

/* -----------------------------------
 * Cursor Pagination Implementation
 * ----------------------------------- */
export function createCursorState<T>(): CursorState<T> {
  return {
    data: [],
    currentPage: 1,
    hasMore: true,
    isLoadingMore: false,
    meta: {
      path: '',
      per_page: 10,
      next_cursor: null,
      prev_cursor: null,
    },
    links: {
      first: null,
      last: null,
      prev: null,
      next: null,
    },
  };
}

export function createCursorActions<Key extends string, Model = unknown>(
  options: PaginationOptions<Key, CursorPaginatorResponse<Model>, CursorState<Model>>,
): PaginationActions<Key, CursorPaginatorResponse<Model>> {
  return createPaginationActions({
    ...options,
    transform: (state, res) => {
      state.data = res.data;
      state.meta = res.meta;
      state.links = res.links;
      state.currentPage++;
      state.hasMore = !!res.meta.next_cursor;
    },
  });
}

export function createCursorGetters<Key extends string, Model>(
  key: Key,
): PaginationGetters<Key, Model, CursorState<Model>> {
  return createPaginationGetters(key);
}

/* -----------------------------------
 * Types
 * ----------------------------------- */
export type {
  CursorPaginatorResponse,
  CursorState,
  LengthAwarePaginatorResponse,
  LengthAwareState,
  SimplePaginatorResponse,
  SimpleState,
  PaginatedModuleState,
  PaginationActions,
  PaginationGetters,
  PaginationOptions,
  PaginationRequest,
  PaginationActionKeys,
  StoreContext,
};
