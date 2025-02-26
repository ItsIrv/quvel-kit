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

/**
 * The properties accepted by the component.
 */
const props = defineProps({
  taskErrors: {
    type: Object as () => { message?: string; errors?: Record<string, string[]> | string },
    default: () => ({ message: '', errors: {} }),
  },
});

/**
 * Extracts the most relevant error message from `taskErrors`.
 */
const errorMessage = computed(() => {
  if (props.taskErrors.message) return props.taskErrors.message;

  const errors = props.taskErrors.errors;
  if (!errors || typeof errors !== 'object') return '';

  if (typeof errors === 'string') return errors;
  if (Array.isArray(errors)) return errors.length > 0 ? errors[0] : '';

  const firstKey = Object.keys(errors)[0];
  return firstKey ? errors[firstKey]?.[0] ?? '' : '';
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
