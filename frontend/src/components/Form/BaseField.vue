<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/services/ContainerService';
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
    type: String as () => 'text' | 'password' | 'email' | 'textarea' | 'search' | 'tel' | 'file' | 'url' | 'time' | 'date' | 'datetime-local' | 'number',
    default: 'text',
  },
  schema: {
    type: Object as () => ZodSchema<string> | undefined,
    default: undefined,
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
</script>

<template>
  <q-input
    v-model="inputValue"
    lazy-rules
    filled
    :autocomplete="type === 'password' ? 'current-password' : 'username'"
    :type="type"
    class="col-12 q-mt-sm"
    :label="label"
    :rules="schema ? [container.validation.createTranslatedValidationRule(schema, label)] : []"
    :error-message="errorMessage"
    :error="error"
  />
</template>
