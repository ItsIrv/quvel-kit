<script lang="ts" setup>
import { computed } from 'vue';
import { passwordSchema } from 'src/utils/validators/commonValidators';
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
const password = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});
</script>

<template>
  <BaseField
    v-model="password"
    :label="$t('auth.forms.common.password')"
    name="password"
    type="password"
    autocomplete="current-password"
    :schema="passwordSchema()"
    :error-message="errorMessage"
    :error="error"
  />
</template>
