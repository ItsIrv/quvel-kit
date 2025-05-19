<script lang="ts" setup>
/**
 * PasswordResetForm.vue
 *
 * Form component for password reset requests.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useRecaptcha } from 'src/modules/Core/composables/useRecaptcha';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { PasswordResetService } from 'src/modules/Auth/services/PasswordResetService';
import { useScopedService } from 'src/modules/Core/composables/useScopedService';

/**
 * Emits
 */
const emit = defineEmits(['success', 'switch-form', 'reset-success']);

/**
 * Services
 */
const container = useContainer();
const { isLoaded, execute: executeRecaptcha } = useRecaptcha();
// Instead of using container.get which keeps the service in memory
// we use useScopedService which creates a new instance of the service
// and disposes of it when the component is unmounted.
const passwordResetService = useScopedService(PasswordResetService);

/**
 * Refs
 */
const email = ref('');
const authForm = ref<HTMLFormElement>();

/**
 * Password Reset Task
 *
 * Handles password reset request with reCAPTCHA verification.
 */
const resetTask = container.task.newTask({
  task: async () => {
    const recaptchaToken = await executeRecaptcha('password_reset');

    return await passwordResetService.sendPasswordResetLink(email.value, recaptchaToken);
  },
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel(undefined, true)],
  successHandlers: () => {
    emit('reset-success');
    resetForm();
  },
});

/**
 * Resets the form fields to their initial values.
 */
function resetForm() {
  email.value = '';
  authForm.value?.reset();
  resetTask.reset();
}

/**
 * Handles form submission.
 */
function onSubmit() {
  if (!isLoaded.value) {
    resetTask.errors.value.set(
      'recaptcha',
      container.i18n.t('auth.status.errors.captcha_not_loaded')
    );

    return;
  }

  void resetTask.run();
}

/**
 * Switch to login form
 */
function switchToLogin() {
  emit('switch-form', 'login');
}

defineExpose({
  resetForm,
});
</script>

<template>
  <q-form
    ref="authForm"
    @submit.prevent="onSubmit"
  >
    <p class="text-base mb-4">
      {{ $t('auth.forms.password.resetDescription') }}
    </p>

    <EmailField v-model="email" />

    <!-- Errors -->
    <TaskErrors
      class="tw:mt-2"
      :task-errors="resetTask.errors.value"
    />

    <!-- Links -->
    <div class="tw:pt-4 tw:text-base">
      <span>
        <a
          class="underline cursor-pointer"
          @click="switchToLogin"
        >
          {{ $t('auth.forms.login.link') }}
        </a>
      </span>
    </div>

    <!-- Buttons -->
    <div class="tw:mt-6 tw:flex tw:justify-end tw:gap-4">
      <q-btn
        flat
        class="Button"
        @click="emit('success')"
      >
        {{ $t('common.buttons.cancel') }}
      </q-btn>

      <q-btn
        unelevated
        class="PrimaryButton tw:hover:bg-primary-600"
        type="submit"
        :loading="resetTask.isActive.value"
        :disabled="resetTask.isActive.value"
      >
        {{ $t('auth.forms.password.resetButton') }}
      </q-btn>
    </div>
  </q-form>
</template>
