<script lang="ts" setup>
import { computed } from 'vue';
import { emailSchema } from 'src/utils/validators/commonValidators';
import BaseField from './BaseField.vue'
/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: String,
    required: true,
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
 * Computed
 */
const email = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});
</script>

<template>
  <BaseField
    v-model="email"
    :label="$t('auth.forms.common.email')"
    name="email"
    type="email"
    :schema="emailSchema()"
    :error-message="errorMessage"
    :error="error"
  />
</template>
