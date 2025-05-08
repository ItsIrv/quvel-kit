<script lang="ts" setup>
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useXsrf } from 'src/modules/Core/composables/useXsrf';
import { useMetaConfig } from 'src/modules/Core/composables/useMetaConfig';
import { onMounted } from 'vue';
import { loadTheme } from 'src/modules/Core/utils/themeUtil';
import { mapStatusToType, normalizeKey, useQueryMessageHandler } from 'src/modules/Core/composables/useQueryMessageHandler';

defineOptions({
  /**
   * Pre-fetch the user on page load.
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
      //
    }
  },
});

/**
 * Composables
 */
useXsrf();
useMetaConfig();
useQueryMessageHandler({
  key: 'message',
  normalize: (val) => normalizeKey(val),
  type: (val) => mapStatusToType(val),
});

onMounted(() => {
  loadTheme();
});
</script>

<template>
  <router-view :class="{
    NativeMobile: $q.platform.is.nativeMobile,
  }" />
</template>
