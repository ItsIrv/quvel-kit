import type {
  LengthAwarePaginatorResponse,
  LengthAwareRequest,
  LengthAwareState,
} from 'src/modules/Core/types/laravel.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

export type StoreContext<Prefix extends string> = {
  [key in Prefix]: LengthAwareState<unknown>;
} & {
  $container: ServiceContainer;
} & LengthAwareActions<Prefix>;

type Fetcher<Prefix extends string> = (
  this: StoreContext<Prefix>,
  options: LengthAwareRequest,
) => Promise<LengthAwarePaginatorResponse<unknown>>;

export type LengthAwareActionKeys<Prefix extends string> =
  | `${Prefix}Fetch`
  | `${Prefix}Next`
  | `${Prefix}Previous`
  | `${Prefix}Reload`;

interface LengthAwareOptions<Prefix extends string> {
  stateKey: Prefix;
  fetcher: Fetcher<Prefix>;
  prefix: Prefix;
}

export type LengthAwareActions<Prefix extends string> = {
  [K in LengthAwareActionKeys<Prefix>]: (
    this: StoreContext<Prefix>,
    ...args: K extends `${Prefix}Fetch`
      ? [options?: LengthAwareRequest, clearPrevious?: boolean]
      : []
  ) => Promise<void>;
};

/**
 * Generates scoped pagination actions using the current store context, 100% typed.
 */

export function createLengthAwareActions<Key extends string>({
  stateKey,
  fetcher,
  prefix,
}: LengthAwareOptions<Key>): LengthAwareActions<Key> {
  const fetchKey = `${prefix}Fetch` as const;
  const nextKey = `${prefix}Next` as const;
  const prevKey = `${prefix}Previous` as const;
  const reloadKey = `${prefix}Reload` as const;

  return {
    async [fetchKey](this: StoreContext<Key>, options = {}, clearPrevious = true) {
      const state = this[stateKey];

      if (clearPrevious) state.data = [];
      state.isLoadingMore = true;

      try {
        const { data, meta, links } = await fetcher.call(this, options);
        state.data = data;
        state.meta = meta;
        state.links = links;
        state.currentPage = meta.current_page;
        state.hasMore = meta.current_page < meta.last_page;
      } catch (e) {
        console.error(`[Pagination] ${stateKey}Fetch failed`, e);
      } finally {
        state.isLoadingMore = false;
      }
    },

    async [nextKey](this: StoreContext<Key>) {
      const state = this[stateKey];
      if (!state.hasMore || state.isLoadingMore) return;
      /** @ts-expect-error Type Issue  */
      await this[fetchKey]({ page: state.currentPage + 1 }, false);
    },

    async [prevKey](this: StoreContext<Key>) {
      const state = this[stateKey];
      if (state.currentPage <= 1) return;
      /** @ts-expect-error Type Issue  */
      await this[fetchKey]({ page: state.currentPage - 1 }, false);
    },

    async [reloadKey](this: StoreContext<Key>) {
      /** @ts-expect-error Type Issue  */
      await this[fetchKey]({ page: 1 });
    },
  } as LengthAwareActions<Key>;
}

export function createLengthAwareState<T>(): LengthAwareState<T> {
  return {
    type: 'length-aware',
    data: [],
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
    currentPage: 1,
    hasMore: true,
    isLoadingMore: false,
  };
}

export type LengthAwareGetters<Model, Key extends string> = {
  [K in `get${Capitalize<Key>}`]: (state: Record<Key, LengthAwareState<Model>>) => Model[];
} & {
  [K in `has${Capitalize<Key>}`]: (state: Record<Key, LengthAwareState<Model>>) => boolean;
};

export function createLengthAwareGetters<T, Key extends string>(
  key: Key,
): LengthAwareGetters<T, Key> {
  const capitalizedKey = capitalize(key);

  const getKey = `get${capitalizedKey}`;
  const hasKey = `has${capitalizedKey}`;

  return {
    [getKey]: (state: Record<Key, LengthAwareState<T>>) => state[key].data,
    [hasKey]: (state: Record<Key, LengthAwareState<T>>) => state[key].data.length > 0,
  } as LengthAwareGetters<T, Key>;
}

function capitalize<T extends string>(s: T): Capitalize<T> {
  return (s.charAt(0).toUpperCase() + s.slice(1)) as Capitalize<T>;
}
