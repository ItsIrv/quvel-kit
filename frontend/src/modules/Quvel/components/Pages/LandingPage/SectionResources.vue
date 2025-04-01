<script lang="ts" setup>
import ProjectLinks from 'src/modules/Quvel/components/Pages/LandingPage/ProjectLinks.vue';
</script>

<template>
  <section class="Resources mt-24 px-8 w-full max-w-6xl text-center">
    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Resources & Quick Links</h2>
    <p class="text-gray-600 dark:text-gray-400 text-lg mb-10">
      Essential resources for development, testing, and documentation.
    </p>

    <ProjectLinks
      class="LandingProjectLinks grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
    />
  </section>
</template>
