<script lang="ts" setup>
/**
 * SignupForm.vue
 *
 * Form component for user registration.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useRecaptcha } from 'src/modules/Core/composables/useRecaptcha';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import PasswordConfirmField from 'src/modules/Auth/components/Form/PasswordConfirmField.vue';
import NameField from 'src/modules/Auth/components/Form/NameField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { AuthStatusEnum } from 'src/modules/Auth/enums/AuthStatusEnum';

/**
 * Emits
 */
const emit = defineEmits(['success', 'switch-form', 'registration-success']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();
const { isLoaded, execute } = useRecaptcha();

/**
 * Refs
 */
const email = ref('');
const name = ref('');
const password = ref('');
const passwordConfirm = ref('');
const authForm = ref<HTMLFormElement>();

/**
 * Signup Task
 *
 * Handles user signup with reCAPTCHA verification.
 */
const signupTask = task.newTask<AuthStatusEnum>({
  task: async () => {
    try {
      // Get reCAPTCHA token
      const recaptchaToken = await execute('signup');

      // Send token along with signup data
      return await sessionStore.signUp(
        email.value,
        password.value,
        name.value,
        recaptchaToken
      );
    } catch (error) {
      // Handle reCAPTCHA errors
      if (error instanceof Error) {
        throw new Error(i18n.t('auth.status.errors.captcha'));
      }
      throw error;
    }
  },
  errorHandlers: <ErrorHandler[]>[task.errorHandlers.Laravel()],
  successHandlers: (status) => {
    if (status === AuthStatusEnum.REGISTER_SUCCESS) {
      emit('registration-success');
    }

    resetForm();
  },
});

/**
 * Resets the form fields to their initial values.
 */
function resetForm() {
  email.value = '';
  password.value = '';
  passwordConfirm.value = '';
  name.value = '';
  authForm.value?.reset();
  signupTask.reset();
}

/**
 * Handles form submission.
 */
function onSubmit() {
  if (!isLoaded.value) {
    signupTask.errors.value.set('recaptcha', i18n.t('auth.status.errors.captcha_not_loaded'));

    return;
  }

  void signupTask.run();
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
    <EmailField
      v-model="email"
      :error-message="signupTask.errors.value.get('email')"
      :error="signupTask.errors.value.has('email')"
    />

    <NameField
      v-model="name"
      :error-message="signupTask.errors.value.get('name')"
      :error="signupTask.errors.value.has('name')"
    />

    <PasswordField
      v-model="password"
      :error-message="signupTask.errors.value.get('password')"
      :error="signupTask.errors.value.has('password')"
    />

    <div class="overflow-hidden">
      <PasswordConfirmField
        v-model="passwordConfirm"
        :password="password"
      />
    </div>

    <!-- Errors -->
    <TaskErrors
      class="mt-2"
      :task-errors="signupTask.errors.value"
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
        :loading="signupTask.isActive.value"
        :disabled="signupTask.isActive.value"
      >
        {{ $t('auth.forms.signup.button') }}
      </q-btn>
    </div>
  </q-form>
</template>
