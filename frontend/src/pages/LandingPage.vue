<script lang="ts" setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { storeToRefs } from 'pinia';
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import LoginDialog from 'src/components/Dialogs/LoginDialog.vue';
import { useSessionStore } from 'src/stores/sessionStore';
import UserMenu from 'src/components/MainLayout/UserMenu.vue';

/**
 * Pixels to hide navigation bar on scroll
 */
const NAV_HIDE_THRESHOLD = 50;

/**
 * Refs
 */
const { isAuthenticated } = storeToRefs(useSessionStore());
const isHidden = ref(false);
const showAuthForm = ref(false);

function handleScroll() {
  const scrollY = window.scrollY;
  isHidden.value = scrollY > NAV_HIDE_THRESHOLD;
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="LandingPage min-h-screen w-screen bg-main-gradient flex flex-col items-center">
    <!-- Header -->
    <header class="relative flex justify-center pt-6">
      <nav :class="[
        'GenericBorder fixed top-6 flex items-center justify-between gap-6 px-8 py-3 rounded-full shadow-md GenericCardGradient transition-all duration-300 w-[90%] max-w-4xl',
        isHidden
          ? 'opacity-0 -translate-y-10 pointer-events-none'
          : 'opacity-100 translate-y-0 pointer-events-auto',
      ]">
        <div class="row items-center">
          <!-- Branding -->
          <span class="text-2xl font-bold text-gray-900 dark:text-white">
            <span class="text-blue-500">Qu</span><span class="text-orange-600">Vel</span> Kit
          </span>

          <!-- Navigation Links -->
          <ul class="hidden sm:!flex gap-10 text-gray-700 dark:text-gray-300 font-mono ml-10">
            <li>
              <a
                href="https://github.com/ItsIrv/quvel-kit/tree/main/docs"
                class="SmallGlow transition"
              >
                Docs
              </a>
            </li>

            <li>
              <a
                href="https://github.com/ItsIrv/quvel-kit"
                class="SmallGlow transition"
              >
                GitHub
              </a>
            </li>
          </ul>
        </div>

        <!-- User Section -->
        <div class="flex items-center gap-4">
          <ThemeSwitcher class="SmallGlow" />
          <LanguageSwitcher class="q-hidden-sm" />

          <template v-if="isAuthenticated">
            <UserMenu />
          </template>

          <template v-else>
            <button
              class="bg-transparent GenericBorder SmallGlow text-stone-700 dark:text-gray-300 px-4 py-1 rounded-lg shadow-md hover:bg-primary-600 transition"
              @click="showAuthForm = true"
            >
              Log in
            </button>
          </template>
        </div>
      </nav>
    </header>

    <!-- Main Features Section -->
    <section class="mt-32 px-8 w-full max-w-6xl text-center">
      <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-10">Main Features</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="GenericCard">
          <h3 class="text-h5 text-gray-900 dark:text-white">Laravel & Quasar</h3>
          <p class="text-gray-600 dark:text-gray-300 q-mt-sm text-body1">
            Run <span class="text-bold">Quasar</span> in
            <span class="text-bold">SSR + SPA mode</span> with
            <span class="text-bold">Laravel</span> as the backend.
            <span class="text-bold">Built-in APIs</span> to
            <span class="text-bold">pre-fetch data</span>,
            <span class="text-bold">validate forms</span>, and handle
            <span class="text-bold">errors</span>.
          </p>
        </div>

        <div class="GenericCard">
          <h3 class="text-h5 text-gray-900 dark:text-white">Multi-Tenant Ready</h3>
          <p class="text-gray-600 dark:text-gray-300 q-mt-sm text-body1">
            Use <span class="text-bold">Quasar</span> to support
            <span class="text-bold">multiple devices</span>, while using
            <span class="text-bold">one Laravel API</span> and database for handling
            <span class="text-bold">multiple customers</span>.
          </p>
        </div>

        <div class="GenericCard">
          <h3 class="text-h5 text-gray-900 dark:text-white">Optimized Development</h3>
          <p class="text-gray-600 dark:text-gray-300 q-mt-sm text-body1">
            Get an <span class="text-bold">HTTPS Traefik-based Docker</span> setup with
            <span class="text-bold">HMR</span>, <span class="text-bold">Testing</span>,
            <span class="text-bold">Dashboards</span>, and those are just a few.
          </p>
        </div>
      </div>
    </section>

    <!-- Handy Resources Section -->
    <section class="Resources mt-24 px-8 w-full max-w-6xl text-center">
      <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Resources & Quick Links</h2>
      <p class="text-gray-600 dark:text-gray-400 text-lg mb-10">
        Essential resources for development, testing, and documentation.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a
          href="https://github.com/ItsIrv/quvel-kit/"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">GitHub</h4>
          <p class="text-body1">View the QuVel Kit repository.</p>
        </a>

        <a
          href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">Docs</h4>
          <p class="text-body1">Read the full documentation.</p>
        </a>

        <a
          href="https://api.quvel.127.0.0.1.nip.io"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">API</h4>
          <p class="text-body1">Check out the backend API.</p>
        </a>

        <a
          href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">Vitest</h4>
          <p class="text-body1">Run frontend unit tests.</p>
        </a>

        <a
          href="https://coverage-api.quvel.127.0.0.1.nip.io"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">Coverage Reports</h4>
          <p class="text-body1">Analyze code coverage.</p>
        </a>

        <a
          href="http://localhost:8080"
          class="GenericCard"
        >
          <h4 class="text-xl font-semibold">Traefik</h4>
          <p class="text-body1">Manage local networking.</p>
        </a>
      </div>
    </section>
  </div>

  <LoginDialog v-model="showAuthForm" />
</template>
