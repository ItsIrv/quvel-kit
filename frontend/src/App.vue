<template>
  <router-view />
</template>

<script lang="ts" setup>
import { useSessionStore } from 'src/stores/sessionStore';
import { onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { createApi } from './utils/axiosUtil';
import { XsrfName } from './models/Session';

defineOptions({
  /**
   * Pre-fetch the user on page load.
   *
   * TODO: We want to avoid fetching the user on every page load in production.
   */
  async preFetch({ store }) {
    await useSessionStore(store).fetchSession();
  }
})

/**
 * Called on browser to fetch the XSRF cookie from Laravel if we do not have it.
 */
onMounted(() => {
  const xsrf = useQuasar().cookies.get(XsrfName);

  if (!xsrf) {
    try {
      void createApi().get('/');
    } catch {
      //
    }
  }
})
</script>
