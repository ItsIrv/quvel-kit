<script lang="ts" setup>
import { watch } from 'vue';
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import CatalogSection from 'src/modules/Catalog/components/CatalogSection.vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';

defineOptions({
  /**
   * Pre-fetch data for the landing page components.
   */
  async preFetch({ store, ssrContext }) {
    if (ssrContext) {
      await useCatalogStore(store).catalogItemsFetch();
    } else {
      void useCatalogStore(store).catalogItemsFetch();
    }
  },
});

/**
 * Services
 */
const catalogStore = useCatalogStore();
const sessionStore = useSessionStore();

/**
 * Watchers
 */
watch(
  () => sessionStore.isAuthenticated,
  () => {
    void catalogStore.catalogItemsFetch();
  },
);
</script>

<template>
  <div class="LandingPage flex-grow">
    <!-- Catalog section -->
    <CatalogSection />
  </div>
</template>
