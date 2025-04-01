<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import type { ZodSchema } from 'zod';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  type: {
    type: String as () =>
      | 'text'
      | 'password'
      | 'email'
      | 'textarea'
      | 'search'
      | 'tel'
      | 'file'
      | 'url'
      | 'time'
      | 'date'
      | 'datetime-local'
      | 'number',
    default: 'text',
  },
  schema: {
    type: Object as () => ZodSchema<string> | undefined,
    default: undefined,
  },
  rules: {
    type: Array as () => ((val: string) => true | string)[],
    default: () => [],
  },
  errorMessage: {
    type: String,
    default: '',
  },
  error: {
    type: Boolean,
    default: false,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Services
 */
const container = useContainer();

/**
 * Computed Property for Model Binding
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

/**
 * Computed Property for Validation Rules
 * - If `schema` is provided, use `container.validation.createInputRule(schema, label)`.
 * - Otherwise, use the provided `rules` array.
 */
const computedRules = computed(() => {
  return props.schema
    ? [container.validation.createInputRule(props.schema, props.label)]
    : props.rules;
});
</script>

<template>
  <q-input
    v-model="inputValue"
    lazy-rules
    filled
    dense
    :type="type"
    class="col-12 q-mt-sm"
    :label="label"
    :rules="computedRules"
    :error-message="errorMessage"
    :error="error"
  />
</template>
