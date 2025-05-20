<script lang="ts" setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';
import AuthMenu from 'src/modules/Quvel/components/Layouts/MainLayout/AuthMenu.vue';
import MenuList from 'src/modules/Quvel/components/Layouts/MainLayout/MenuList.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-right-drawer', 'open-left-drawer']);

/**
 * Scroll threshold for header transformations
 */
const SCROLL_THRESHOLD = 50;

/**
 * Refs
 */
const scrollY = ref(0);
const isScrolled = ref(false);
const route = useRoute();

/**
 * Computed properties
 */
const isLandingPage = computed(() => {
  return route.meta?.landerBackground === true;
});

/**
 * Handles scroll event and updates header state
 */
function handleScroll() {
  // Use requestAnimationFrame for better performance
  window.requestAnimationFrame(() => {
    scrollY.value = window.scrollY;
    isScrolled.value = scrollY.value > SCROLL_THRESHOLD;
  });
}

/**
 * Lifecycle hooks
 */
onMounted(() => {
  // Initial scroll position check
  handleScroll();
  window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <header class="tw:w-full tw:fixed tw:top-0 tw:left-0 tw:z-50">
    <nav :class="[
      'dynamic-header tw:w-full tw:transition-all tw:duration-300 tw:ease-in-out',
      isScrolled ? 'scrolled' : '',
      isLandingPage ? 'landing-header' : '',
      'tw:py-3 tw:px-4 md:tw:px-8'
    ]">
      <div class="tw:container tw:mx-auto tw:flex tw:items-center tw:justify-between">
        <!-- Logo Section -->
        <div class="tw:flex tw:items-center">
          <QuvelKit
            :link="true"
            class="tw:transition-all tw:duration-300"
          />

          <!-- Navigation Links - Desktop -->
          <MenuList class="tw:flex tw:gap-8 tw:ml-10 tw:font-medium" />
        </div>

        <!-- User Section -->
        <div class="tw:flex tw:items-center tw:gap-4">
          <!-- Auth Menu -->
          <AuthMenu
            @login-click="emits('login-click')"
            @open-left-drawer="emits('open-left-drawer')"
          />

          <!-- Theme & Language - Desktop -->
          <div class="tw:hidden tw:md:flex tw:gap-3 tw:items-center">
            <ThemeSwitcher />
            <div class="tw:h-4 tw:w-px tw:bg-gray-300 tw:dark:bg-gray-700 tw:opacity-50"></div>
            <LanguageSwitcher />
          </div>

          <!-- Mobile Menu Button -->
          <div class="tw:flex tw:lg:hidden">
            <q-btn
              flat
              round
              :ripple="false"
              :icon="isScrolled ? 'eva-menu-outline' : 'eva-menu-2-outline'"
              class="tw:text-current tw:transition-all tw:duration-300"
              @click="emits('open-right-drawer')"
            />
          </div>
        </div>
      </div>
    </nav>
  </header>
</template>
