<template>
  <router-view />
</template>

<script lang="ts" setup>
import { useSessionStore } from 'src/stores/sessionStore';
import { useXsrf } from 'src/composables/useXsrf';
import { useTheme } from 'src/composables/useTheme';
import { useMetaConfig } from './composables/useMetaConfig';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   *
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store },) {
    await useSessionStore(store).fetchSession();
  }
})

useTheme();
useXsrf();
useMetaConfig('A Modern Hybrid App Framework');
</script>
