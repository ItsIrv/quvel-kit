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
