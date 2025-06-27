<script setup lang="ts">
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';

const sessionStore = useSessionStore();
</script>

<template>
  <ul>
    <!-- Authenticated user links -->
    <li v-if="sessionStore.isAuthenticated">
      <router-link
        to="/dashboard"
        class="quvel-link"
      >
        {{ $t('dashboard.title') }}
      </router-link>
    </li>

    <li>
      <a
        href="https://github.com/ItsIrv/quvel-kit/tree/main/docs"
        class="quvel-link"
      >
        {{ $t('quvel.landing.docs') }}
      </a>
    </li>

    <li>
      <a
        href="https://github.com/ItsIrv/quvel-kit"
        class="quvel-link"
      >
        {{ $t('quvel.landing.github') }}
      </a>
    </li>
  </ul>
</template>
