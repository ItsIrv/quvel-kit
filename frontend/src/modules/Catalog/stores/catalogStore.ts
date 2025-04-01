import { acceptHMRUpdate, defineStore } from 'pinia';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import { LengthAwareRequest, PaginationLinks, PaginationMeta } from 'src/types/laravel.types';

/**
 * Interface defining the structure of the catalog state.
 */
interface CatalogState {
  catalogItems: {
    items: CatalogItem[];
    meta: PaginationMeta | null;
    links: PaginationLinks | null;
    currentPage: number;
    hasMore: boolean;
    isLoadingMore: boolean;
  };
}

/**
 * Interface defining getters for the catalog store.
 */
type CatalogGetters = {
  getItems: (state: CatalogState) => CatalogItem[];
};

/**
 * Interface defining actions for the catalog store.
 */
interface CatalogActions {
  fetchCatalogItems(filters?: Record<string, string>): Promise<void>;
}

/**
 * Pinia store for managing the catalog items.
 */
export const useCatalogStore = defineStore<'catalog', CatalogState, CatalogGetters, CatalogActions>(
  'catalog',
  {
    state: (): CatalogState => ({
      catalogItems: {
        items: [],
        meta: null,
        links: null,
        currentPage: 1,
        hasMore: true,
        isLoadingMore: false,
      },
    }),

    getters: {
      /**
       * Retrieves all loaded catalog items.
       */
      getItems: (state) => state.catalogItems.items,
    },

    actions: {
      /**
       * Fetches catalog items from the backend using the CatalogService.
       */
      async fetchCatalogItems(options: LengthAwareRequest = {}): Promise<void> {
        const service = this.$container.getService<CatalogService>('catalog');
        if (!service) return;

        try {
          const { data, meta, links } = await service.fetchCatalogs(options);

          this.catalogItems.items = data;
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
