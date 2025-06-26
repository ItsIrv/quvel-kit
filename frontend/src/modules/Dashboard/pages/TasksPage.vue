<template>
  <q-page class="q-pa-md">
    <div class="text-h5 q-mb-md">{{ $t(`dashboard.nav.${routeName}`) }}</div>
    <q-card flat bordered>
      <q-card-section>
        <p class="text-grey-7">
          This is a placeholder page. Implement your {{ routeName }} interface here.
        </p>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const routeName = computed(() => {
  const name = route.name as string;
  return name.split('-').pop() || 'page';
});
</script>
