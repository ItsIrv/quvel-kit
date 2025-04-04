import { acceptHMRUpdate, defineStore } from 'pinia';
import { CatalogService } from 'src/modules/Catalog/sevices/CatalogService';
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';
import { LengthAwareRequest, LengthAwareState } from 'src/modules/Core/types/laravel.types';
import {
  createLengthAwareActions,
  createLengthAwareGetters,
  createLengthAwareState,
  LengthAwareActions,
  LengthAwareGetters,
} from 'src/modules/Core/stores/helpers/Pagination/LengthAware';

/**
 * Interface defining the structure of the catalog state.
 */
interface CatalogState {
  catalogItems: LengthAwareState<CatalogItem>;
}

/**
 * Interface defining getters for the catalog store.
 */
type CatalogGetters = LengthAwareGetters<CatalogItem, 'catalogItems'>;

/**
 * Interface defining actions for the catalog store.
 */
type CatalogActions = LengthAwareActions<'catalogItems'>;

/**
 * Pinia store for managing the catalog items.
 */
export const useCatalogStore = defineStore<'catalog', CatalogState, CatalogGetters, CatalogActions>(
  'catalog',
  {
    state: (): CatalogState => ({
      catalogItems: createLengthAwareState<CatalogItem>(),
    }),

    getters: {
      ...createLengthAwareGetters<CatalogItem, 'catalogItems'>('catalogItems'),
    },

    actions: {
      ...createLengthAwareActions<'catalogItems'>({
        stateKey: 'catalogItems',
        prefix: 'catalogItems',
        async fetcher(options: LengthAwareRequest) {
          const service = this.$container.getService<CatalogService>('catalog');
          if (!service) throw new Error('[CatalogStore] CatalogService not found');

          return await service.fetchCatalogs(options);
        },
      }),
    },
  },
);

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useCatalogStore, import.meta.hot));
}
