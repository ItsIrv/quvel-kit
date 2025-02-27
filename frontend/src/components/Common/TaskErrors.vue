<script lang="ts" setup>
/**
 * TaskErrors.vue
 *
 * A component to display errors from a task.
 *
 * Props:
 * - `taskErrors`: The errors from a task, including the main error and additional errors.
 */
import { ErrorBag } from 'src/types/error.types';
import { computed } from 'vue';

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
 * Checks if `message` is already inside `errors`, avoiding duplicates.
 */
// const isMessageInErrors = computed(() => {

//   return false;
// });

/**
 * Extracts the most relevant error message.
 * - If `message` is already inside `errors`, we ignore `message` to prevent duplication.
 * - If `errors` exist, extract the first error.
 * - Otherwise, fallback to `message`.
 */
const errorMessage = computed(() => {
  return props.taskErrors.get('message')
});
</script>

<template>
  <transition
    appear
    enter-active-class="animated fadeIn"
    leave-active-class="animated fadeOut"
  >
    <q-banner
      v-if="errorMessage"
      class="bg-negative text-white"
      dense
      rounded
    >
      {{ errorMessage }}
    </q-banner>
  </transition>
</template>

<style scoped></style>
