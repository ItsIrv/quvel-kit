<script lang="ts" setup>
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useXsrf } from 'src/modules/Core/composables/useXsrf';
import { useMetaConfig } from 'src/modules/Core/composables/useMetaConfig';
import { onMounted, watch } from 'vue';
import { loadTheme } from 'src/modules/Core/utils/themeUtil';
import { useOAuthMessageHandler } from 'src/modules/Auth/composables/useOAuthMessageHandler';
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store, ssrContext }) {
    try {
      if (ssrContext) {
        // On SSR, we have to await
        await Promise.all([
          useSessionStore(store).fetchSession(),
          useNotificationStore(store).fetchNotifications(),
        ]);
      } else {
        // On the client we don't have to await unless we want to.
        // If you do await this will block rendering of the page to until the user is fetched.
        void useSessionStore(store).fetchSession();
        void useNotificationStore(store).fetchNotifications();
      }
    } catch {
      // TODO: Handle flow on unauthorized.
    }
  },
});

/**
 * Composables
 */
useXsrf();
useMetaConfig();
useOAuthMessageHandler();

/**
 * Services
 */
const sessionStore = useSessionStore();
const notificationStore = useNotificationStore();

/**
 * Watchers
 */
watch(
  () => sessionStore.getUser?.id,
  (userId) => {
    if (userId) {
      void notificationStore.subscribeToSocket(userId);
    } else {
      notificationStore.unsubscribeFromSocket();
    }
  },
  {
    immediate: true,
  },
);

onMounted(() => {
  loadTheme();
});
</script>

<template>
  <router-view :class="{
    NativeMobile: $q.platform.is.nativeMobile,
  }" />
</template>
