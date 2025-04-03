import { acceptHMRUpdate, defineStore } from 'pinia';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import { LengthAwareRequest, LengthAwareState } from 'src/modules/Core/types/laravel.types';

/**
 * Interface defining the structure of the catalog state.
 */
interface CatalogState {
  catalogItems: LengthAwareState<CatalogItem>;
}

/**
 * Interface defining getters for the catalog store.
 */
type CatalogGetters = {
  getItems: (state: CatalogState) => CatalogItem[];
  hasItems: (state: CatalogState) => boolean;
};

/**
 * Interface defining actions for the catalog store.
 */
interface CatalogActions {
  fetchCatalogItems(options?: LengthAwareRequest): Promise<void>;
}

/**
 * Pinia store for managing the catalog items.
 */
export const useCatalogStore = defineStore<'catalog', CatalogState, CatalogGetters, CatalogActions>(
  'catalog',
  {
    state: (): CatalogState => ({
      catalogItems: {
        type: 'length-aware',
        data: [],
        meta: {
          current_page: 1,
          from: 1,
          last_page: 1,
          per_page: 1,
          to: 1,
          total: 1,
          links: [],
          path: '',
        },
        links: {
          first: '',
          last: '',
          prev: '',
          next: '',
        },
        currentPage: 1,
        hasMore: true,
        isLoadingMore: false,
      },
    }),

    getters: {
      /**
       * Retrieves all loaded catalog items.
       */
      getItems: (state) => state.catalogItems.data,
      hasItems: (state) => state.catalogItems.data.length > 0,
    },

    actions: {
      /**
       * Fetches catalog items from the backend using the CatalogService.
       */
      async fetchCatalogItems(
        options: LengthAwareRequest = {},
        clearPrevious = true,
      ): Promise<void> {
        const service = this.$container.getService<CatalogService>('catalog');
        if (!service) return;

        if (clearPrevious) {
          this.catalogItems.data = [];
        }

        try {
          const { data, meta, links } = await service.fetchCatalogs(options);

          this.catalogItems.data = data;
          this.catalogItems.meta = meta;
          this.catalogItems.links = links;
          this.catalogItems.currentPage = meta.current_page;
          this.catalogItems.hasMore = meta.current_page < meta.last_page;
        } catch (e) {
          console.error('[CatalogStore] fetchCatalogItems failed', e);
        }
      },
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCatalogStore, import.meta.hot));
}
