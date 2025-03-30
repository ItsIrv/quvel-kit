<script lang="ts" setup>
import { useSessionStore } from 'src/stores/sessionStore';
import { useXsrf } from 'src/composables/useXsrf';
import { useMetaConfig } from './composables/useMetaConfig';
import { onMounted } from 'vue';
import { loadTheme } from './utils/themeUtil';
import { useOAuthMessageHandler } from 'src/composables/useOAuthMessageHandler';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   *
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store }) {
    try {
      await useSessionStore(store).fetchSession();
    } catch {
      // TODO: Handle flow on unauthorized.
    }
  },
});

useXsrf();
useMetaConfig('A Modern Hybrid App Framework');
useOAuthMessageHandler();

onMounted(() => {
  loadTheme();
});
</script>

<template>
  <router-view
    :class="{
      NativeMobile: $q.platform.is.nativeMobile,
      Mobile: $q.platform.is.mobile,
    }"
  />
</template>
