<script lang="ts" setup>
/**
 * MenuRightDrawer.vue is the right side drawer that opens
 * up from the hamburger button on the main nav.
 */
import { computed } from 'vue';
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';

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
  >
    <div class="p-6 flex flex-col gap-4">
      <div class="row gap-8 mx-auto">
        <ThemeSwitcher />
        <LanguageSwitcher />
      </div>

      <q-separator />

      <a
        href="https://github.com/ItsIrv/quvel-kit/tree/main/docs"
        class="text-lg text-gray-700 dark:text-gray-300"
      >
        Docs
      </a>

      <a
        href="https://github.com/ItsIrv/quvel-kit"
        class="text-lg text-gray-700 dark:text-gray-300"
      >
        GitHub
      </a>

      <q-separator />
    </div>
  </q-drawer>
</template>
