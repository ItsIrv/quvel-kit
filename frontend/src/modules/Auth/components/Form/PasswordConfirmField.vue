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
  password: {
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
</script>

<template>
  <BaseField
    v-model="password"
    :label="$t('auth.forms.common.passwordConfirm')"
    name="password"
    type="password"
    :rules="[
      (value: string) => value === props.password || $t('auth.status.errors.mismatch'),
    ]"
    autocomplete="current-password"
  />
</template>
