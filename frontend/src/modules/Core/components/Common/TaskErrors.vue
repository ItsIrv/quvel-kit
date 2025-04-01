<script lang="ts" setup>
/**
 * TaskErrors.vue
 *
 * A component to display errors from a task.
 *
 * Props:
 * - `taskErrors`: The errors from a task, including the main error and additional errors.
 */
import { computed } from 'vue';
import { ErrorBag } from 'src/modules/Core/types/error.types';
import FadeInOut from 'src/modules/Core/components/Transitions/FadeInOut.vue';

/**
 * Props for the component.
 */
const props = defineProps({
  taskErrors: {
    type: Object as () => ErrorBag,
    default: () => ({ message: '', errors: {} }),
  },
});

/**
 * Extracts the most relevant error message.
 * - If `message` is already inside `errors`, we ignore `message` to prevent duplication.
 * - If `errors` exist, extract the first error.
 * - Otherwise, fallback to `message`.
 */
const errorMessage = computed(() => {
  const { taskErrors } = props;

  // Get the first available error from `taskErrors`
  const firstError = Array.from(taskErrors.values())[0];

  // If firstError exists, use it. Otherwise, fallback to message.
  return firstError || taskErrors.get('message') || '';
});
</script>

<template>
  <FadeInOut>
    <q-banner v-if="errorMessage" class="bg-negative text-white" dense rounded>
      {{ errorMessage }}
    </q-banner>
  </FadeInOut>
</template>
