<script lang="ts" setup>
import { defineAsyncComponent, ref, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';
import ClientOnly from 'src/modules/Core/components/Misc/ClientOnly.vue';
import PageHeader from 'src/modules/Quvel/components/Layouts/MainLayout/PageHeader.vue';
import PageFooter from 'src/modules/Quvel/components/Layouts/MainLayout/PageFooter.vue';
import LanderBackground from '../components/Pages/LandingPage/LanderBackground.vue';

const AuthDialog = defineAsyncComponent(() => import('src/modules/Auth/components/Dialogs/AuthDialog.vue'));
const MenuRightDrawer = defineAsyncComponent(() => import('src/modules/Quvel/components/Layouts/MainLayout/MenuRightDrawer.vue'));
const MenuLeftDrawer = defineAsyncComponent(() => import('src/modules/Quvel/components/Layouts/MainLayout/MenuLeftDrawer.vue'));

/**
 * Services
 */
const sessionStore = useSessionStore();
const notificationStore = useNotificationStore();

/**
 * Route
 */
const route = useRoute();

/**
 * Computed
 */
const routeMeta = computed(() => route.meta);

/**
 * Computed property to determine if the current route has a custom background
 */
const haslanderBackground = computed(() => {
  return routeMeta.value?.landerBackground === true;
});

/**
 * Computed property to get the background class for the current route
 */
const backgroundClass = computed(() => {
  if (haslanderBackground.value) {
    return routeMeta.value?.backgroundClass || '';
  }
  return 'MainGradient';
});

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
  <q-layout class="MainLayout">
    <div :class="[backgroundClass, 'tw:min-h-screen tw:flex tw:flex-col tw:relative tw:overflow-hidden']">
      <!-- Grid overlay and particles for custom backgrounds -->
      <LanderBackground v-if="haslanderBackground" />

      <!-- Header -->
      <PageHeader
        @login-click="onLoginClick"
        @open-right-drawer="onOpenRightDrawer"
        @open-left-drawer="onOpenLeftDrawer"
      />

      <!-- Main content area -->
      <div class="tw:flex-grow tw:mt-10">
        <router-view />
      </div>

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
  </q-layout>
</template>
