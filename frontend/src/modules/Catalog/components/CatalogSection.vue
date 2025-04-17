<script lang="ts" setup>
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import CatalogItem from 'src/modules/Catalog/components/CatalogItem.vue';

const catalogStore = useCatalogStore();

async function onPageChange(page: number) {
  await catalogStore.catalogItemsFetch({ page });
}
</script>

<template>
  <section class="CatalogSection max-w-6xl mx-auto px-4 mt-[100px]">
    <q-inner-loading :showing="!catalogStore.hasCatalogItems" />

    <div class="py-8">
      <!-- Grid of Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <template v-for="item in catalogStore.getCatalogItems" :key="item.id">
          <CatalogItem :item="item" />
        </template>
      </div>

      <!-- Pagination -->
      <div class="mt-10 flex justify-center">
        <q-pagination
          v-if="catalogStore.hasCatalogItems"
          :model-value="catalogStore.catalogItems.meta?.current_page ?? 0"
          :max="catalogStore.catalogItems.meta?.last_page ?? 0"
          :max-pages="5"
          :ellipses="false"
          color="pink-4"
          boundary-links
          boundary-numbers
          @update:model-value="onPageChange"
        />
      </div>
    </div>
  </section>
</template>
