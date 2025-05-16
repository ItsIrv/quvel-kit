<script lang="ts" setup>
import { computed, PropType } from 'vue';
import { passwordSchema } from 'src/modules/Core/validators/commonValidators';
import BaseField from '../../../Core/components/Form/BaseField.vue';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: String,
    required: true,
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
