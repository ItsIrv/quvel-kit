<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';
import AuthMenu from 'src/modules/Quvel/components/Layouts/MainLayout/AuthMenu.vue';
import MenuList from 'src/modules/Quvel/components/Layouts/MainLayout/MenuList.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
import { useWindowEvent } from 'src/modules/Core/composables/useWindowEvent';

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

useWindowEvent('scroll', handleScroll, { passive: true });

onMounted(() => {
  handleScroll();
});
</script>

<template>
  <header class="PageHeader">
    <nav :class="[
      'PageHeader-Nav',
      isScrolled ? 'PageHeader-Nav--scrolled' : '',
      isLandingPage ? 'PageHeader-Nav--landing' : ''
    ]">
      <div class="PageHeader-Container">
        <!-- Logo Section -->
        <div class="PageHeader-LogoSection">
          <QuvelKit :link="true" />

          <!-- Navigation Links - Desktop -->
          <MenuList class="PageHeader-NavLinks" />
        </div>

        <!-- User Section -->
        <div class="PageHeader-UserSection">
          <!-- Auth Menu -->
          <AuthMenu
            @login-click="emits('login-click')"
            @open-left-drawer="emits('open-left-drawer')"
          />

          <!-- Theme & Language - Desktop -->
          <div class="PageHeader-DesktopControls">
            <ThemeSwitcher />
            <div class="PageHeader-Divider"></div>
            <LanguageSwitcher />
          </div>

          <!-- Mobile Menu Button -->
          <div class="PageHeader-MobileMenu">
            <q-btn
              flat
              round
              :ripple="false"
              :icon="isScrolled ? 'eva-menu-outline' : 'eva-menu-2-outline'"
              class="PageHeader-MenuButton"
              @click="emits('open-right-drawer')"
            />
          </div>
        </div>
      </div>
    </nav>
  </header>
</template>

<style lang="scss">
@reference '../../../../../css/tailwind.scss';

/**
 * PageHeader Component Styles
 */
.PageHeader {
  @apply tw:w-full tw:fixed tw:top-0 tw:left-0 tw:z-50;

  &-Nav {
    @apply tw:w-full tw:transition-all tw:duration-300 tw:ease-in-out tw:py-3 tw:px-4 tw:md:px-8;

    // Dynamic states
    &--scrolled {
      @apply tw:bg-white/80 tw:dark:bg-gray-900/80;
      backdrop-filter: blur(12px) saturate(180%);
      -webkit-backdrop-filter: blur(12px) saturate(180%);
      @apply tw:shadow-sm;
    }

    &--landing {
      @apply tw:text-gray-800 tw:dark:text-white;

      &:not(.PageHeader-Nav--scrolled) {
        @apply tw:text-gray-800 tw:dark:text-white tw:py-5;

        .q-btn {
          @apply tw:text-gray-800 tw:dark:text-white;
        }
      }
    }
  }

  &-Container {
    @apply tw:container tw:mx-auto tw:flex tw:items-center tw:justify-between;
  }

  &-LogoSection {
    @apply tw:flex tw:items-center;

    .QuvelKit {
      @apply tw:transition-all tw:duration-300;
    }
  }

  &-NavLinks {
    @apply tw:hidden tw:md:flex tw:gap-8 tw:ml-10 tw:text-lg;
  }

  &-UserSection {
    @apply tw:flex tw:items-center;
  }

  &-DesktopControls {
    @apply tw:hidden tw:md:flex tw:gap-3 tw:items-center;
  }

  &-Divider {
    @apply tw:h-4 tw:w-px tw:bg-gray-300 tw:dark:bg-gray-700 tw:opacity-50;
  }

  &-MobileMenu {
    @apply tw:flex tw:md:hidden;
  }

  &-MenuButton {
    @apply tw:text-current tw:transition-all tw:duration-300;
  }
}
</style>
