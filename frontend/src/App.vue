<template>
  <router-view />
</template>

<script lang="ts" setup>
import { useSessionStore } from 'stores/session-store';
import { createApi } from './boot/axios';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   *
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store, ssrContext }) {
    await useSessionStore(store).fetchSession(
      createApi(ssrContext)
    );
  }
})
</script>
