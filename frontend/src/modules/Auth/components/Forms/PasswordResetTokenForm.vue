<script lang="ts" setup>
/**
 * PasswordResetTokenForm.vue
 *
 * Form component for resetting password with a token.
 * This form is displayed after a user clicks on the password reset link in their email.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import PasswordConfirmField from 'src/modules/Auth/components/Form/PasswordConfirmField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { PasswordResetService } from 'src/modules/Auth/services/PasswordResetService';
import { useScopedService } from 'src/modules/Core/composables/useScopedService';

/**
 * Props & Emits
 */
const props = defineProps<{
  token: string;
}>();

const emit = defineEmits(['success', 'switch-form']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const passwordResetService = useScopedService(PasswordResetService);

/**
 * Refs
 */
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const authForm = ref<HTMLFormElement>();

/**
 * Password Reset Task
 *
 * Handles password reset with token.
 */
const resetTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.passwordReset'),
  },
  task: async () =>
    await passwordResetService.resetPassword(
      props.token,
      email.value,
      password.value,
      passwordConfirmation.value
    ),
  errorHandlers: <ErrorHandler[]>[task.errorHandlers.Laravel(undefined, true)],
  successHandlers: () => {
    resetForm();
    emit('switch-form', 'login');
  },
});


/**
 * Resets the form fields to their initial values.
 */
function resetForm() {
  email.value = '';
  password.value = '';
  passwordConfirmation.value = '';
  authForm.value?.reset();
  resetTask.reset();
}

/**
 * Handles form submission.
 */
function onSubmit() {
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
    <p class="tw:text-base tw:mb-4">
      {{ $t('auth.forms.passwordReset.tokenDescription') }}
    </p>

    <EmailField v-model="email" />

    <PasswordField
      v-model="password"
      class="tw:mt-4"
    />

    <PasswordConfirmField
      v-model="passwordConfirmation"
      :password="password"
      class="tw:mt-4"
    />

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
        {{ $t('auth.forms.passwordReset.tokenButton') }}
      </q-btn>
    </div>
  </q-form>
</template>
