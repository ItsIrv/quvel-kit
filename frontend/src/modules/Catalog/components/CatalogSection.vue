<script lang="ts" setup>
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import CatalogItem from 'src/modules/Catalog/components/CatalogItem.vue';

const catalogStore = useCatalogStore();

async function onPageChange(page: number) {
  await catalogStore.catalogItemsFetch({ page });
}

async function reloadCatalog() {
  await catalogStore.catalogItemsFetch();
}
</script>

<template>
  <section class="CatalogSection tw:max-w-6xl tw:mx-auto tw:px-4 tw:mt-[100px]">
    <q-inner-loading :showing="catalogStore.catalogItems.isLoadingMore" />

    <div
      v-if="!catalogStore.catalogItems.hasLoaded && !catalogStore.catalogItems.isLoadingMore"
      class="tw:py-8 tw:text-center"
    >
      <p class="tw:text-lg tw:text-gray-600">Unable to load catalog items. Please try again later.</p>
      <q-btn
        color="primary"
        class="tw:mt-4"
        @click="reloadCatalog"
      >Retry</q-btn>
    </div>

    <div
      v-else-if="catalogStore.catalogItems.hasLoaded && !catalogStore.hasCatalogItems && !catalogStore.catalogItems.isLoadingMore"
      class="tw:py-8 tw:text-center"
    >
      <p class="tw:text-lg tw:text-gray-600">No catalog items found.</p>
    </div>

    <div
      v-else
      class="tw:py-8 tw:text-center"
    >
      <!-- Grid of Cards -->
      <div class="tw:grid tw:grid-cols-1 tw:sm:grid-cols-2 tw:lg:grid-cols-3 tw:gap-6">
        <template
          v-for="item in catalogStore.getCatalogItems"
          :key="item.id"
        >
          <CatalogItem :item="item" />
        </template>
      </div>

      <!-- Pagination -->
      <div class="tw:mt-10 tw:flex tw:justify-center">
        <q-pagination
          v-if="catalogStore.hasCatalogItems"
          :model-value="catalogStore.catalogItems.meta?.current_page ?? 0"
          :max="catalogStore.catalogItems.meta?.last_page ?? 0"
          :max-pages="5"
          :ellipses="false"
          color="pink-4"
          direction-links
          boundary-links
          boundary-numbers
          @update:model-value="onPageChange"
        />
      </div>
    </div>
  </section>
</template>
