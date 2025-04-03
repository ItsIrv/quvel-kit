import {
  BasePaginationState,
  CursorPaginatorResponse,
  CursorState,
  LengthAwarePaginatorResponse,
  LengthAwareRequest,
  LengthAwareState,
  PaginatedModule,
  PaginatedModuleState,
  PaginationLinks,
  PaginationMeta,
  PaginationType,
  SimplePaginatorResponse,
  SimpleState,
} from 'src/modules/Core/types/laravel.types';

function isLengthAwarePaginator<T = unknown>(
  response: unknown,
): response is LengthAwarePaginatorResponse<T> {
  return (
    typeof response === 'object' &&
    Array.isArray((response as { data: T[] }).data) &&
    typeof (response as { meta: unknown }).meta === 'object'
  );
}

function isSimplePaginator<T>(response: unknown): response is SimplePaginatorResponse<T> {
  return (
    typeof response === 'object' &&
    Array.isArray((response as { data: T[] }).data) &&
    typeof (response as { current_page: unknown }).current_page === 'number'
  );
}

function isCursorPaginator<T>(response: unknown): response is CursorPaginatorResponse<T> {
  return (
    typeof response === 'object' &&
    Array.isArray((response as { data: T[] }).data) &&
    typeof (response as { cursor: unknown }).cursor === 'number'
  );
}

export function usePaginatedModule<T>(config: {
  paginationType: 'length-aware';
  fetcher: (
    options: LengthAwareRequest,
  ) => Promise<LengthAwarePaginatorResponse<T> | false | undefined>;
}): PaginatedModule<T, LengthAwareState<T>>;

export function usePaginatedModule<T>(config: {
  paginationType: 'simple';
  fetcher: (options: LengthAwareRequest) => Promise<SimplePaginatorResponse<T> | false | undefined>;
}): PaginatedModule<T, SimpleState<T>>;

export function usePaginatedModule<T>(config: {
  paginationType: 'cursor';
  fetcher: (options: LengthAwareRequest) => Promise<CursorPaginatorResponse<T> | false | undefined>;
}): PaginatedModule<T, CursorState<T>>;

export function usePaginatedModule<
  T,
  B extends PaginatedModuleState<T> = PaginatedModuleState<T>,
>(config: {
  fetcher: (
    options: LengthAwareRequest,
  ) => Promise<
    | LengthAwarePaginatorResponse<T>
    | SimplePaginatorResponse<T>
    | CursorPaginatorResponse<T>
    | false
    | undefined
  >;
  paginationType: PaginationType;
}): PaginatedModule<T, B> {
  const base: BasePaginationState<T> = {
    type: config.paginationType,
    data: [],
    isLoadingMore: false,
    hasMore: true,
    currentPage: 1,
  };

  const state = (() => {
    switch (config.paginationType) {
      case 'length-aware':
        return {
          ...base,
          type: 'length-aware',
          meta: {} as PaginationMeta,
          links: {} as PaginationLinks,
        } as LengthAwareState<T>;

      case 'simple':
        return {
          ...base,
          type: 'simple',
          nextPageUrl: null,
          prevPageUrl: null,
          path: '',
        } as SimpleState<T>;

      case 'cursor':
        return {
          ...base,
          type: 'cursor',
          nextPageUrl: null,
          prevPageUrl: null,
          cursor: { next: null, prev: null },
        } as CursorState<T>;

      default:
        throw new Error(`[Pagination] Unsupported type: ${String(config.paginationType)}`);
    }
  })() as B;

  const fetch = async (options: LengthAwareRequest = {}, clearPrevious = true) => {
    state.isLoadingMore = true;

    try {
      const res = await config.fetcher(options);
      if (clearPrevious) state.data = [];

      if (isLengthAwarePaginator<T>(res) && state.type === 'length-aware') {
        state.data = res.data;
        state.meta = res.meta;
        state.links = res.links;
        state.currentPage = res.meta.current_page;
        state.hasMore = res.meta.current_page < res.meta.last_page;
      } else if (isSimplePaginator<T>(res) && state.type === 'simple') {
        state.data = res.data;
        state.currentPage = res.current_page;
        state.nextPageUrl = res.next_page_url;
        state.prevPageUrl = res.prev_page_url;
        state.path = res.path;
        state.hasMore = !!res.next_page_url;
      } else if (isCursorPaginator<T>(res) && state.type === 'cursor') {
        state.data = res.data;
        state.cursor = res.cursor;
        state.currentPage++;
        state.nextPageUrl = res.next_page_url;
        state.prevPageUrl = res.prev_page_url;
        state.hasMore = !!res.cursor.next;
      } else {
        console.warn('[Pagination] Invalid response shape');
      }
    } finally {
      state.isLoadingMore = false;
    }
  };

  const reload = () => fetch({ page: 1 });
  const next = () =>
    state.hasMore && !state.isLoadingMore
      ? fetch({ page: state.currentPage + 1 }, false)
      : Promise.resolve();

  const previous = () =>
    state.currentPage > 1 ? fetch({ page: state.currentPage - 1 }, false) : Promise.resolve();

  return { state, fetch, reload, next, previous };
}
