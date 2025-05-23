<script lang="ts" setup>
import IDEWindow from '../components/Pages/LandingPage/IDEWindow.vue';
import HeroSection from '../components/Pages/LandingPage/HeroSection.vue';
</script>

<template>
  <div class="LandingPage tw:flex-grow tw:relative tw:overflow-hidden">
    <!-- Hero Content -->
    <div class="tw:container tw:mx-auto tw:px-4 tw:relative tw:z-10">
      <!-- Hero Section -->
      <HeroSection />

      <!-- IDE Window -->
      <IDEWindow />
    </div>
  </div>
</template>

<style lang="scss"></style>
