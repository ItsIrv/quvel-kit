<script lang="ts" setup>
import { createValidationRule } from 'src/utils/validationUtil';
import { passwordSchema } from 'src/utils/validators/commonValidators';
import { computed, defineEmits } from 'vue';
import { useI18n } from 'vue-i18n';

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

const emits = defineEmits(['update:modelValue']);

const password = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

const $t = useI18n().t;
</script>

<template>
  <q-input
    v-model="password"
    lazy-rules
    filled
    autocomplete="current-password"
    type="password"
    class="col-12 q-mt-sm"
    :label="$t('auth.forms.common.password')"
    :rules="[createValidationRule(passwordSchema)]"
    :error-message="errorMessage"
    :error="error"
  />
</template>
