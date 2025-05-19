<template>
  <q-card class="GenericCardGradient">
    <q-img
      :src="item.image"
      alt="Catalog Item Picture"
    />

    <q-card-section>
      <div class="text-h6 tw-text-red-500">{{ item.name }}</div>
      <div
        v-if="item.user"
        class="text-subtitle2"
      >Author: {{ item.user.name }}</div>

      <q-badge
        :label="item.is_public ? 'Public' : 'Private'"
        :color="item.is_public ? 'positive' : 'info'"
      />

      <div v-if="item.metadata?.rating">
        <q-rating
          :model-value="item.metadata.rating"
          color="yellow"
          size="xs"
          readonly
          icon="eva-star-outline"
          icon-selected="eva-star"
        />
      </div>

    </q-card-section>

    <q-card-section class="q-pt-none h-18">
      {{ item.description }}
    </q-card-section>

    <div
      v-if="item.metadata?.tags"
      class="pl-3 pb-2"
    >
      <q-chip
        v-for="tag in item.metadata.tags"
        :key="tag"
        :label="tag"
        size="sm"
        color="primary"
        text-color="white"
      />
    </div>
  </q-card>
</template>

<script lang="ts" setup>
import { CatalogItem } from 'src/modules/Catalog/models/CatalogItem';

defineProps({
  item: {
    type: Object as () => CatalogItem,
    required: true,
  },
});
</script>
