<script lang="ts" setup>
import { computed, PropType } from 'vue';
import { nameSchema } from 'src/modules/Core/utils/validators/commonValidators';
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
const email = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});
</script>

<template>
  <BaseField
    v-model="email"
    :label="$t('auth.forms.common.name')"
    name="name"
    type="text"
    autocomplete="name"
    :schema="nameSchema()"
    :error-message="errorMessage"
    :error="error"
  />
</template>
