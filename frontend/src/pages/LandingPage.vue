<script lang="ts" setup>
import { ref } from 'vue';
import AuthDialog from 'src/components/Dialogs/AuthDialog.vue';
import MenuRightDrawer from 'src/components/MainLayout/Drawers/MenuRightDrawer.vue';
import PageHeader from 'src/components/Pages/LandingPage/PageHeader.vue';
import SectionFeatures from 'src/components/Pages/LandingPage/SectionFeatures.vue';
import SectionResources from 'src/components/Pages/LandingPage/SectionResources.vue';
import PageFooter from 'src/components/Pages/LandingPage/PageFooter.vue';

/**
 * Refs
 */
const showAuthForm = ref(false);
const drawerOpen = ref(false);

function onLoginClick() {
  showAuthForm.value = true;
  drawerOpen.value = false;
}
</script>

<template>
  <div class="LandingPage MainGradient min-h-screen flex flex-col items-center">
    <!-- Header -->
    <PageHeader
      @login-click="onLoginClick"
      @open-drawer="drawerOpen = true"
    />

    <!-- Main Features Section -->
    <SectionFeatures />

    <!-- Handy Resources Section -->
    <SectionResources />

    <!-- Footer -->
    <PageFooter />
  </div>

  <!-- Mobile Drawer -->
  <MenuRightDrawer
    v-model="drawerOpen"
    @login-click="onLoginClick"
  />

  <!-- Login Dialog -->
  <AuthDialog v-model="showAuthForm" />
</template>

<style lang="scss" scoped></style>
