<script lang="ts" setup>
import { computed } from 'vue';
import { Dark } from 'quasar';
import { toggleTheme } from 'src/modules/Core/utils/themeUtil';
import ClientOnly from 'src/modules/Core/components/Misc/ClientOnly.vue';

/**
 * ThemeSwitcher component for toggling dark mode.
 */

/**
 * Computed property that returns whether the current theme is dark.
 */
const isDark = computed(() => Dark.isActive);

/**
 * Toggles the theme between dark and light.
 */
function toggleDarkMode(): void {
  toggleTheme();
}
</script>

<template>
  <ClientOnly>
    <div class="ThemeSwitcherMinimal">
      <q-btn
        flat
        dense
        round
        class="tw:flex tw:items-center tw:justify-center tw:rounded-full tw:w-8 tw:h-8 tw:bg-gray-100 tw:dark:bg-gray-800 tw:border tw:border-gray-200 tw:dark:border-gray-700 tw:text-sm tw:focus:outline-none"
        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        @click="toggleDarkMode"
      >
        <q-icon
          :name="isDark ? 'eva-sun-outline' : 'eva-moon-outline'"
          size="18px"
          :class="isDark ? 'tw:text-yellow-400' : 'tw:text-indigo-500'"
        />
      </q-btn>
    </div>
  </ClientOnly>
</template>

<style lang="scss" scoped>
.ThemeSwitcherMinimal {
  display: inline-block;
}
</style>
