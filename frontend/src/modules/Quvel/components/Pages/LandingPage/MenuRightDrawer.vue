<script lang="ts" setup>
/**
 * MenuRightDrawer.vue is the right side drawer that opens
 * up from the hamburger button on the main nav.
 */
import { computed } from 'vue';
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';
import ProjectLinks from 'src/modules/Quvel/components/Pages/LandingPage/ProjectLinks.vue';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Computed
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});



</script>

<template>
  <q-drawer
    v-model="inputValue"
    class="MainGradient"
    side="right"
    overlay
    behavior="mobile"
  >
    <div class="p-6 flex flex-col gap-4">
      <div class="row gap-8 mx-auto">
        <ThemeSwitcher />
        <LanguageSwitcher />
      </div>

      <q-separator />

      <!-- Project Links -->
      <ProjectLinks class="DrawerProjectLinks col space-y-6 text-gray-700 dark:text-gray-300 font-mono" />
    </div>
  </q-drawer>
</template>

<style lang="scss"></style>
