<script lang="ts" setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';
import AuthMenu from 'src/modules/Quvel/components/Layouts/MainLayout/AuthMenu.vue';
import MenuList from 'src/modules/Quvel/components/Layouts/MainLayout/MenuList.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-right-drawer', 'open-left-drawer']);

/**
 * Pixels to hide navigation bar on scroll
 */
const NAV_HIDE_THRESHOLD = 50;

/**
 * Services
 */
const sessionStore = useSessionStore();

/**
 * Refs
 */
const isHidden = ref(false);

/**
 * Handles scroll event and updates isHidden state.
 */
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
  <header class="relative flex justify-center pt-6">
    <nav :class="[
      'LanderNav',
      isHidden
        ? 'opacity-0 -translate-y-10 pointer-events-none'
        : 'opacity-100 translate-y-0 pointer-events-auto',
      sessionStore.isAuthenticated ? 'max-w-5xl' : 'max-w-2xl',
    ]">
      <div class="row items-center">
        <QuvelKit :link="true" />

        <!-- Navigation Links -->
        <MenuList class="hidden sm:!flex gap-10 text-gray-700 dark:text-gray-300 font-mono ml-10" />
      </div>

      <!-- User Section -->
      <div class="row items-center gap-4">
        <AuthMenu
          @login-click="emits('login-click')"
          @open-left-drawer="emits('open-left-drawer')"
        />

        <div class="flex sm:!hidden">
          <q-btn
            dense
            flat
            round
            icon="eva-menu-outline"
            class="sm:hidden text-gray-700 dark:text-gray-300"
            @click="emits('open-right-drawer')"
          />
        </div>
      </div>
    </nav>
  </header>
</template>
