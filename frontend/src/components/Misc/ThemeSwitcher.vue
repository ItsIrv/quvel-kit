<template>
  <q-btn
    dense
    round
    flat
    @click="toggleDarkMode"
  >
    <q-icon
      :name="isDark ? 'eva-moon-outline' : 'eva-sun-outline'"
      size="24px"
    />
  </q-btn>
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { Dark, LocalStorage } from 'quasar';

const isDark = computed(() => Dark.isActive);

function toggleDarkMode(): void {
  const newMode = !Dark.isActive;
  Dark.set(newMode);
  LocalStorage.set('theme', newMode ? 'dark' : 'light');
}
</script>
