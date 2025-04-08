import { acceptHMRUpdate, defineStore } from 'pinia';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import {
  createCursorActions,
  createCursorGetters,
  createCursorState,
  createLengthAwareActions,
  createLengthAwareGetters,
  createLengthAwareState,
  createSimpleActions,
  createSimpleGetters,
  createSimpleState,
  CursorPaginatorResponse,
  CursorState,
  LengthAwarePaginatorResponse,
  LengthAwareState,
  PaginationActions,
  PaginationGetters,
  PaginationRequest,
  SimplePaginatorResponse,
  SimpleState,
} from 'src/modules/Core/helpers/Pagination';

/**
 * Interface defining the structure of the catalog state.
 */
interface CatalogState {
  catalogItems: LengthAwareState<CatalogItem>;
  catalogSimple: SimpleState<CatalogItem>;
  catalogCursor: CursorState<CatalogItem>;
}

/**
 * Interface defining getters for the catalog store.
 */
type CatalogGetters = PaginationGetters<
  'catalogItems',
  CatalogItem,
  LengthAwareState<CatalogItem>
> &
  PaginationGetters<'catalogSimple', CatalogItem, SimpleState<CatalogItem>> &
  PaginationGetters<'catalogCursor', CatalogItem, CursorState<CatalogItem>>;

/**
 * Interface defining actions for the catalog store.
 */
type CatalogActions = PaginationActions<'catalogItems', LengthAwarePaginatorResponse<CatalogItem>> &
  PaginationActions<'catalogSimple', SimplePaginatorResponse<CatalogItem>> &
  PaginationActions<'catalogCursor', CursorPaginatorResponse<CatalogItem>>;

/**
 * Pinia store for managing the catalog items.
 */
export const useCatalogStore = defineStore<'catalog', CatalogState, CatalogGetters, CatalogActions>(
  'catalog',
  {
    state: (): CatalogState => ({
      catalogItems: createLengthAwareState<CatalogItem>(),
      catalogSimple: createSimpleState<CatalogItem>(),
      catalogCursor: createCursorState<CatalogItem>(),
    }),

    getters: {
      ...createLengthAwareGetters<'catalogItems', CatalogItem>('catalogItems'),
      ...createSimpleGetters<'catalogSimple', CatalogItem>('catalogSimple'),
      ...createCursorGetters<'catalogCursor', CatalogItem>('catalogCursor'),
    },

    actions: {
      ...createLengthAwareActions<'catalogItems', CatalogItem>({
        stateKey: 'catalogItems',
        async fetcher(options: PaginationRequest) {
          const service = this.$container.getService<CatalogService>('catalog');

          if (!service) throw new Error();

          return await service.fetchCatalogs(options);
        },
      }),
      ...createSimpleActions<'catalogSimple', CatalogItem>({
        stateKey: 'catalogSimple',
        async fetcher(options: PaginationRequest) {
          const service = this.$container.getService<CatalogService>('catalog');

          if (!service) throw new Error();

          return await service.fetchCatalogsSimple(options);
        },
      }),
      ...createCursorActions<'catalogCursor', CatalogItem>({
        stateKey: 'catalogCursor',
        async fetcher(options: PaginationRequest) {
          const service = this.$container.getService<CatalogService>('catalog');

          if (!service) throw new Error();

          return await service.fetchCatalogsCursor(options);
        },
      }),
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCatalogStore, import.meta.hot));
}
