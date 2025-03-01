<script lang="ts" setup>
import { onMounted, onUnmounted, ref } from 'vue';
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import { useSessionStore } from 'src/stores/sessionStore';

const { isAuthenticated, user } = useSessionStore();
const isHidden = ref(false);

const handleScroll = () => {
  const scrollY = window.scrollY;
  isHidden.value = scrollY > 50;
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="LandingPage min-h-screen w-screen bg-main-gradient flex flex-col items-center">
    <header class="relative flex justify-center pt-6">
      <nav :class="[
        'fixed top-6 flex items-center justify-between gap-6 px-8 py-3 rounded-full shadow-lg bg-stone-200 dark:bg-gray-800 border border-stone-100 dark:border-gray-700 transition-all duration-300 w-[90%] max-w-4xl',
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
          <ul class="hidden md:!flex gap-10 text-gray-700 dark:text-gray-300 font-mono ml-6">
            <li>
              <a
                href="https://github.com/ItsIrv/quvel-kit/tree/main/docs"
                class="GlowEffect transition"
              >
                Docs
              </a>
            </li>

            <li>
              <a
                href="https://github.com/ItsIrv/quvel-kit"
                class="GlowEffect transition"
              >
                GitHub
              </a>
            </li>
          </ul>
        </div>

        <!-- User Section -->
        <div class="flex items-center gap-4">
          <ThemeSwitcher />
          <LanguageSwitcher class="q-hidden-sm" />

          <template v-if="isAuthenticated">
            <span class="text-gray-700 dark:text-gray-300 text-sm hidden md:inline">
              {{ user?.email }}
            </span>
            <img
              src="https://i.pravatar.cc/100"
              alt="User Avatar"
              class="w-10 h-10 rounded-full border border-stone-400 dark:border-gray-600 shadow-sm"
            />
          </template>

          <template v-else>
            <button
              class="bg-transparent border border-stone-400 dark:border-gray-600 text-stone-700 dark:text-gray-300 px-4 py-1 rounded-lg shadow-md hover:bg-primary-600 transition"
            >
              Log in
            </button>
          </template>
        </div>
      </nav>
    </header>

    <!-- Main Features Section -->
    <section class="Features mt-32 px-8 w-full max-w-6xl text-center">
      <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-10">Main Features</h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="GenericCard">
          <h3 class="text-h5 text-gray-900 dark:text-white">Laravel & Quasar</h3>
          <p class="text-gray-600 dark:text-gray-300 q-mt-sm text-body1">
            Run <span class="text-bold">Quasar</span> in SSR + SPA mode with
            <span class="text-bold">Laravel</span> as the backend.
            <span class="text-bold">Built-in APIs</span> to manage connections.
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
            Get an <span class="text-bold">HTTPS Traefik-based Docker</span> setup with HMR, Testing
            Dashboards, debugging tools, and more.
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
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">GitHub</h4>
          <p>View the QuVel Kit repository.</p>
        </a>

        <a
          href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">Docs</h4>
          <p>Read the full documentation.</p>
        </a>

        <a
          href="https://api.quvel.127.0.0.1.nip.io"
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">API</h4>
          <p>Check out the backend API.</p>
        </a>

        <a
          href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">Vitest</h4>
          <p>Run frontend unit tests.</p>
        </a>

        <a
          href="https://coverage-api.quvel.127.0.0.1.nip.io"
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">Coverage Reports</h4>
          <p>Analyze code coverage.</p>
        </a>

        <a
          href="http://localhost:8080"
          class="ResourceCard"
        >
          <h4 class="text-xl font-semibold">Traefik</h4>
          <p>Manage local networking.</p>
        </a>
      </div>
    </section>
  </div>
</template>
