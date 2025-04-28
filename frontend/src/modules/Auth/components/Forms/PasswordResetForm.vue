<script lang="ts" setup>
/**
 * PasswordResetForm.vue
 *
 * Form component for password reset requests.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';

/**
 * Emits
 */
const emit = defineEmits(['success', 'switch-form', 'reset-success']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();

/**
 * Refs
 */
const email = ref('');
const authForm = ref<HTMLFormElement>();

/**
 * Password Reset Task
 *
 * Handles password reset request.
 */
const resetTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.passwordResetSent'),
  },
  task: async () => await sessionStore.forgotPassword(email.value),
  errorHandlers: <ErrorHandler[]>[task.errorHandlers.Laravel()],
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
    
    <EmailField
      v-model="email"
      :error-message="resetTask.errors.value.get('email')"
      :error="resetTask.errors.value.has('email')"
    />

    <!-- Errors -->
    <TaskErrors
      class="mt-2"
      :task-errors="resetTask.errors.value"
    />

    <!-- Links -->
    <div class="pt-4 text-base">
      <span>
        {{ $t('auth.forms.login.link') }}
        <a
          class="underline cursor-pointer"
          @click="switchToLogin"
        >
          {{ $t('auth.forms.login.button') }}
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
        {{ $t('auth.forms.password.resetButton') }}
      </q-btn>
    </div>
  </q-form>
</template>
