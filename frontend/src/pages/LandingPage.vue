<script lang="ts" setup>
import { ref } from 'vue';
import AuthDialog from 'src/components/Dialogs/AuthDialog.vue';
import MenuRightDrawer from 'src/components/Pages/LandingPage/MenuRightDrawer.vue';
import MenuLeftDrawer from 'src/components/Pages/LandingPage/MenuLeftDrawer.vue';
import PageHeader from 'src/components/Pages/LandingPage/PageHeader.vue';
import SectionFeatures from 'src/components/Pages/LandingPage/SectionFeatures.vue';
import SectionResources from 'src/components/Pages/LandingPage/SectionResources.vue';
import PageFooter from 'src/components/Pages/LandingPage/PageFooter.vue';

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
</script>

<template>
  <div class="LandingPage MainGradient min-h-screen flex flex-col items-center">
    <!-- Header -->
    <PageHeader
      @login-click="onLoginClick"
      @open-right-drawer="onOpenRightDrawer"
      @open-left-drawer="onOpenLeftDrawer"
    />

    <!-- Main Features Section -->
    <SectionFeatures />

    <!-- Handy Resources Section -->
    <SectionResources />

    <!-- Footer -->
    <PageFooter />
  </div>

  <!-- Right Drawer -->
  <MenuRightDrawer
    v-model="isRightDrawerOpen"
    @login-click="onLoginClick"
  />

  <!-- Left Drawer -->
  <MenuLeftDrawer v-model="isLeftDrawerOpen" />

  <!-- Login Dialog -->
  <AuthDialog v-model="showAuthForm" />
</template>

<style lang="scss" scoped></style>
