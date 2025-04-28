<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import type { ZodSchema } from 'zod';
import type { PropType } from 'vue';

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
    type: String as PropType<string | undefined>,
    default: undefined,
    required: false,
  },
  error: {
    type: Boolean as PropType<boolean | undefined>,
    default: undefined,
    required: false,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Services
 */
const { validation } = useContainer();

/**
 * Computed Property for Model Binding
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

/**
 * Computed Property for Validation Rules
 * - If `schema` is provided, use `validation.createInputRule(schema, label)`.
 * - Otherwise, use the provided `rules` array.
 */
const computedRules = computed(() => {
  return props.schema
    ? [validation.createInputRule(props.schema, props.label)]
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
