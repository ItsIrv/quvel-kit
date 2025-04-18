<script lang="ts" setup>
import AuthDialog from 'src/modules/Auth/components/Dialogs/AuthDialog.vue';
import MenuRightDrawer from 'src/modules/Quvel/components/Pages/LandingPage/MenuRightDrawer.vue';
import MenuLeftDrawer from 'src/modules/Quvel/components/Pages/LandingPage/MenuLeftDrawer.vue';
import PageHeader from 'src/modules/Quvel/components/Pages/LandingPage/PageHeader.vue';
import PageFooter from 'src/modules/Quvel/components/Pages/LandingPage/PageFooter.vue';
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import CatalogSection from 'src/modules/Catalog/components/CatalogSection.vue';
import { ref, watch } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import TenantDevWebSocketLogger from 'src/modules/Core/components/TenantDevWebSocketLogger.vue';

defineOptions({
  /**
   * Pre-fetch some catalogs.
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
 * Refs
 */
const showAuthForm = ref(false);
const isRightDrawerOpen = ref(false);
const isLeftDrawerOpen = ref(false);

/**
 * Methods
 */

/**
 * Opens the login dialog
 */
function onLoginClick() {
  showAuthForm.value = true;
  isRightDrawerOpen.value = false;
  isLeftDrawerOpen.value = false;
}

/**
 * Opens the left drawer
 */
function onOpenLeftDrawer() {
  isLeftDrawerOpen.value = true;
}

/**
 * Opens the right drawer
 */
function onOpenRightDrawer() {
  isRightDrawerOpen.value = true;
}

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
  <div class="LandingPage MainGradient min-h-screen flex flex-col">
    <!-- Header -->
    <PageHeader
      @login-click="onLoginClick"
      @open-right-drawer="onOpenRightDrawer"
      @open-left-drawer="onOpenLeftDrawer"
    />

    <!-- Scrollable section -->
    <CatalogSection />

    <!-- Footer -->
    <PageFooter />
  </div>

  <!-- Drawers and dialogs -->
  <MenuRightDrawer
    v-model="isRightDrawerOpen"
    @login-click="onLoginClick"
  />
  <MenuLeftDrawer v-model="isLeftDrawerOpen" />
  <AuthDialog v-model="showAuthForm" />
  <TenantDevWebSocketLogger />
</template>
