<script lang="ts" setup>
import { computed, defineEmits } from 'vue';
import { useI18n } from 'vue-i18n';
import { createValidationRule } from 'src/utils/validationUtil';
import { emailSchema } from 'src/utils/validators/commonValidators';

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

const email = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

const $t = useI18n().t;
</script>

<template>
  <q-input
    v-model="email"
    lazy-rules
    filled
    autocomplete="username"
    type="email"
    class="col-12"
    :label="$t('auth.forms.common.email')"
    :rules="[createValidationRule(emailSchema)]"
    :error-message="errorMessage"
    :error="error"
  />
</template>
