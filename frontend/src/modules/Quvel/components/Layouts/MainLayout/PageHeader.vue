<script lang="ts" setup>
import { onMounted, onUnmounted, ref } from 'vue';
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
  <header>
    <nav :class="[
      'LanderNav',
      isHidden
        ? 'tw:opacity-0 tw:-translate-y-10 tw:pointer-events-none'
        : 'tw:opacity-100 tw:translate-y-0 tw:pointer-events-auto'
    ]">
      <div class="tw:flex tw:items-center">
        <QuvelKit :link="true" />

        <!-- Navigation Links -->
        <MenuList
          class="tw:hidden tw:sm:!flex tw:gap-10 tw:text-gray-700 tw:dark:text-gray-300 tw:font-mono tw:ml-10" />
      </div>

      <!-- User Section -->
      <div class="tw:flex tw:items-center tw:gap-4">
        <AuthMenu
          @login-click="emits('login-click')"
          @open-left-drawer="emits('open-left-drawer')"
        />

        <div class="tw:flex tw:sm:!hidden">
          <q-btn
            dense
            flat
            round
            icon="eva-menu-outline"
            class="tw:sm:hidden tw:text-gray-700 tw:dark:text-gray-300"
            @click="emits('open-right-drawer')"
          />
        </div>
      </div>
    </nav>
  </header>
</template>
