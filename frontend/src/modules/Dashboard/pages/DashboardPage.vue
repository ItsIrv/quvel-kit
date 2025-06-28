<template>
  <q-page>
    <!-- Page Header -->
    <div class="row q-mb-lg">
      <div class="col-12">
        <h1 class="text-h4 q-my-none">Welcome back, {{ userName }}!</h1>
        <p class="text-subtitle1 text-grey-7 q-mb-none">
          Here's your dashboard.
        </p>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';

const sessionStore = useSessionStore();
const userName = computed(() => sessionStore.getUser?.name || 'User');
</script>
