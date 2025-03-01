<script lang="ts" setup>
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import { useSessionStore } from 'src/stores/sessionStore';

const { isAuthenticated, user } = useSessionStore();
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Floating Dynamic Island -->
    <header class="relative flex justify-center pt-6">
      <div
        class="fixed top-6 flex items-center gap-4 rounded-full shadow-lg bg-white-100 dark:bg-gray-800 transition-all duration-300"
        :class="{ 'px-6 py-2 w-80': isAuthenticated, 'px-4 py-1 w-56': !isAuthenticated }"
      >
        <span class="text-lg font-semibold text-gray-900 dark:text-white">QuVel Kit</span>

        <div class="ml-auto">
          <span
            v-if="isAuthenticated"
            class="text-gray-700 dark:text-gray-300 text-sm"
          >
            {{ user?.email }}
          </span>
          <button
            v-else
            class="bg-primary-500 text-white px-4 py-1 rounded-lg shadow-md hover:bg-primary-600 transition"
          >
            Log in
          </button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="flex-grow flex flex-col items-center justify-center text-center">
      <h1 class="text-5xl md:text-7xl font-extrabold text-gray-900 dark:text-white">
        Welcome to QuVel Kit
      </h1>

      <ThemeSwitcher class="q-mt-md" />
    </main>
  </div>
</template>
