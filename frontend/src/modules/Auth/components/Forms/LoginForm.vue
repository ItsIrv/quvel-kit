<script lang="ts" setup>
/**
 * LoginForm.vue
 *
 * Form component for user login.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { useQuasar } from 'quasar';

/**
 * Emits
 */
const emit = defineEmits(['success', 'switch-form']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();
const quasar = useQuasar();

/**
 * Refs
 */
const email = ref('');
const password = ref('');
const authForm = ref<HTMLFormElement>();
const isOAuthRedirecting = ref(false);

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[task.errorHandlers.Laravel()],
  successHandlers: () => {
    emit('success');
    resetForm();
  },
});

/**
 * Resets the form fields to their initial values.
 */
function resetForm() {
  email.value = '';
  password.value = '';
  authForm.value?.reset();
  loginTask.reset();
}

/**
 * Handles form submission.
 */
function onSubmit() {
  void loginTask.run();
}

/**
 * OAuth login handler
 */
function loginWithOAuth(provider: string) {
  void sessionStore.loginWithOAuth(provider, quasar.platform.is.capacitor);
  isOAuthRedirecting.value = true;
  setTimeout(() => {
    isOAuthRedirecting.value = false;
  }, 5000);
}

/**
 * Switch to signup form
 */
function switchToSignup() {
  emit('switch-form', 'signup');
}

/**
 * Switch to password reset form
 */
function switchToPasswordReset() {
  emit('switch-form', 'password-reset');
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
    <!-- Oauth providers -->
    <div class="grid grid-cols-2 gap-2 mt-4 mb-2">
      <q-btn
        class="GenericBorder AccentGradient Button"
        :label="$t('auth.forms.oauth.logInWith', { provider: $t('auth.forms.oauth.google') })"
        unelevated
        :disable="loginTask.isActive.value"
        :loading="isOAuthRedirecting"
        @click="loginWithOAuth('google')"
      />

      <q-btn
        class="GenericBorder Button"
        :label="$t('common.placeholder')"
        unelevated
        :disable="true"
      />
    </div>

    <EmailField
      v-model="email"
      :error-message="loginTask.errors.value.get('email')"
      :error="loginTask.errors.value.has('email')"
    />

    <PasswordField
      v-model="password"
      :error-message="loginTask.errors.value.get('password')"
      :error="loginTask.errors.value.has('password')"
    />

    <!-- Errors -->
    <TaskErrors
      class="mt-2"
      :task-errors="loginTask.errors.value"
    />

    <!-- Links -->
    <div class="pt-4 text-base flex justify-between">
      <span>
        {{ $t('auth.forms.signup.link') }}
        <a
          class="underline cursor-pointer"
          @click="switchToSignup"
        >
          {{ $t('auth.forms.signup.button') }}
        </a>
      </span>

      <a
        class="underline cursor-pointer text-sm"
        @click="switchToPasswordReset"
      >
        {{ $t('auth.forms.password.forgot') }}
      </a>
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
        :loading="loginTask.isActive.value"
        :disabled="loginTask.isActive.value || isOAuthRedirecting"
      >
        {{ $t('auth.forms.login.button') }}
      </q-btn>
    </div>
  </q-form>
</template>
