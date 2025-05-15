<script lang="ts" setup>
import { defineAsyncComponent, ref, watch } from 'vue';
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import CatalogSection from 'src/modules/Catalog/components/CatalogSection.vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';
import ClientOnly from 'src/modules/Core/components/Misc/ClientOnly.vue';
import PageHeader from 'src/modules/Quvel/components/Pages/LandingPage/PageHeader.vue';
import PageFooter from 'src/modules/Quvel/components/Pages/LandingPage/PageFooter.vue';

const AuthDialog = defineAsyncComponent(() => import('src/modules/Auth/components/Dialogs/AuthDialog.vue'));
const MenuRightDrawer = defineAsyncComponent(() => import('src/modules/Quvel/components/Pages/LandingPage/MenuRightDrawer.vue'));
const MenuLeftDrawer = defineAsyncComponent(() => import('src/modules/Quvel/components/Pages/LandingPage/MenuLeftDrawer.vue'));

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
const notificationStore = useNotificationStore();

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


/**
 * Watchers
 */
watch(
  () => sessionStore.getUser?.id,
  (userId) => {
    if (userId) {
      void notificationStore.subscribe(userId);
    } else {
      notificationStore.unsubscribe();
    }
  },
  {
    immediate: true,
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
  <ClientOnly>
    <MenuRightDrawer
      v-model="isRightDrawerOpen"
      @login-click="onLoginClick"
    />

    <MenuLeftDrawer v-model="isLeftDrawerOpen" />
  </ClientOnly>

  <AuthDialog
    v-model="showAuthForm"
    @open="showAuthForm = true"
  />
</template>
