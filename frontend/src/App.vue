<script lang="ts" setup>
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useXsrf } from 'src/modules/Core/composables/useXsrf';
import { useMetaConfig } from 'src/modules/Core/composables/useMetaConfig';
import { onMounted } from 'vue';
import { loadTheme } from 'src/modules/Core/utils/themeUtil';
import { useOAuthMessageHandler } from 'src/modules/Auth/composables/useOAuthMessageHandler';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   *
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store, ssrContext }) {
    try {
      if (ssrContext) {
        // On SSR, we have to await
        await useSessionStore(store).fetchSession();
      } else {
        // On the client we don't have to await unless we want to.
        // If you do await this will block rendering of the page to until the user is fetched.
        void useSessionStore(store).fetchSession();
      }
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
