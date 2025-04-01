<script lang="ts" setup>
import { computed } from 'vue';
import BaseField from '../../../Core/components/Form/BaseField.vue';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: String,
    required: true,
  },
  passwordValue: {
    type: String,
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
const password = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

const errorMessage = computed(() => {
  return props.modelValue && props.modelValue !== props.passwordValue
    ? 'auth.status.errors.mismatch'
    : '';
});
</script>

<template>
  <BaseField
    v-model="password"
    :label="$t('auth.forms.common.passwordConfirm')"
    name="password"
    type="password"
    autocomplete="current-password"
    :error-message="errorMessage ? $t(errorMessage) : ''"
    :error="!!errorMessage"
  />
</template>
