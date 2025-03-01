<template>
  <q-btn
    dense
    round
    flat
    @click="toggleDarkMode"
  >
    <q-icon
      :name="isDark ? 'eva-sun-outline' : 'eva-moon-outline'"
      size="24px"
    />
  </q-btn>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { Dark } from 'quasar';
import { toggleTheme } from 'src/utils/themeUtil';

const isDark = computed(() => Dark.isActive);

function toggleDarkMode(): void {
  toggleTheme();
}
</script>
