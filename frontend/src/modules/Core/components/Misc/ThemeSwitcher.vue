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
    <div class="flex flex-center">
      <q-icon
        :class="['cursor-pointer', 'text-grey-7']"
        :name="isDark ? 'eva-sun-outline' : 'eva-moon-outline'"
        size="24px"
        @click="toggleDarkMode"
      />
    </div>
  </ClientOnly>
</template>
