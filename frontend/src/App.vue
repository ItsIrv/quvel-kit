<template>
  <router-view />
</template>

<script lang="ts" setup>
import { useSessionStore } from 'src/stores/sessionStore';
import { useXsrf } from 'src/composables/mount/useXsrf';
import { useTheme } from 'src/composables/mount/useTheme';

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
</script>
