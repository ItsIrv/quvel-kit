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
import { AuthService } from 'src/modules/Auth/services/AuthService';

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
const container = useContainer();

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
const resetTask = container.task.newTask({
  showNotification: {
    success: () => container.i18n.t('auth.status.success.passwordReset'),
  },
  task: async () =>
    await container.get(AuthService).resetPassword(
      props.token,
      email.value,
      password.value,
      passwordConfirmation.value
    ),
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel(undefined, true)],
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
    <p class="text-base mb-4">
      {{ $t('auth.forms.passwordReset.tokenDescription') }}
    </p>

    <EmailField v-model="email" />

    <PasswordField
      v-model="password"
      class="mt-4"
    />

    <PasswordConfirmField
      v-model="passwordConfirmation"
      :password="password"
      class="mt-4"
    />

    <!-- Errors -->
    <TaskErrors
      class="mt-2"
      :task-errors="resetTask.errors.value"
    />

    <!-- Links -->
    <div class="pt-4 text-base">
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
    <div class="mt-6 flex justify-end gap-4">
      <q-btn
        flat
        class="Button"
        @click="emit('success')"
      >
        {{ $t('common.buttons.cancel') }}
      </q-btn>

      <q-btn
        unelevated
        class="PrimaryButton hover:bg-primary-600"
        type="submit"
        :loading="resetTask.isActive.value"
        :disabled="resetTask.isActive.value"
      >
        {{ $t('auth.forms.passwordReset.tokenButton') }}
      </q-btn>
    </div>
  </q-form>
</template>
