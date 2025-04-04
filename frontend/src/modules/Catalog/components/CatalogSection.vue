<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';

const catalogStore = useCatalogStore();
const isClient = ref(false);

async function onPageChange(page: number) {
  await catalogStore.catalogItemsFetch({ page });
}

onMounted(() => {
  isClient.value = true;
});
</script>

<template>
  <section class="CatalogSection max-w-6xl mx-auto px-4">
    <q-inner-loading :showing="!catalogStore.hasCatalogItems" />

    <div class="py-8">
      <!-- Grid of Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <template v-for="item in catalogStore.getCatalogItems" :key="item.id">
          <q-card class="GenericCardGradient">
            <q-img :src="item.image" alt="Catalog Item Picture" />

            <q-card-section>
              <div class="text-h6">{{ item.name }}</div>
              <div class="text-subtitle2">{{ item.user?.name ?? '' }}</div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              {{ item.description }}
            </q-card-section>
          </q-card>
        </template>
      </div>

      <!-- Pagination -->
      <div class="mt-10 flex justify-center">
        <q-pagination
          v-if="catalogStore.hasCatalogItems"
          color="primary"
          :max="catalogStore.catalogItems.meta?.last_page ?? 0"
          :max-pages="5"
          :model-value="catalogStore.catalogItems.meta?.current_page ?? 0"
          boundary-numbers
          direction-links
          size="md"
          @update:model-value="onPageChange"
        />
      </div>
    </div>
  </section>
</template>
