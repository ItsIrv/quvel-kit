<template>
  <q-page class="q-pa-md">
    <!-- Page Header -->
    <div class="row q-mb-lg">
      <div class="col-12">
        <h1 class="text-h4 q-my-none">Welcome back, {{ userName }}!</h1>
        <p class="text-subtitle1 text-grey-7 q-mb-none">
          Here's your dashboard overview.
        </p>
      </div>
    </div>

    <!-- Welcome Card -->
    <div class="row">
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">Getting Started</div>
            <p class="text-body1">
              Welcome to your dashboard! This is where you'll manage your account and access your tools.
            </p>
          </q-card-section>
        </q-card>
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