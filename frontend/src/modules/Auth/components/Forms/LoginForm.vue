<script lang="ts" setup>
/**
 * LoginForm.vue
 *
 * Form component for user login.
 */
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useScopedService } from 'src/modules/Core/composables/useScopedService';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { TwoFactorChallengeService } from 'src/modules/Auth/services/TwoFactorChallengeService';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import { DashboardRoutes } from 'src/modules/Dashboard/router/constants';

/**
 * Emits
 */
const emit = defineEmits(['success', 'switch-form']);

/**
 * Services
 */
const { task, i18n, config } = useContainer();
const sessionStore = useSessionStore();
const quasar = useQuasar();
const router = useRouter();
const twoFactorChallengeService = useScopedService(TwoFactorChallengeService);

/**
 * Refs
 */
const email = ref('');
const password = ref('');
const twoFactorCode = ref('');
const recoveryCode = ref('');
const showTwoFactorChallenge = ref(false);
const showRecoveryCodeInput = ref(false);
const selectedProvider = ref<string | null>(null);
const authForm = ref<HTMLFormElement>();
const isOAuthRedirecting = ref(false);
const socialiteProviders = config.get('socialiteProviders');

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = task.newTask({
  task: async () => {
    const result = await sessionStore.login(email.value, password.value);

    if (result.requiresTwoFactor) {
      showTwoFactorChallenge.value = true;
      return;
    }

    // Regular login success
    emit('success');
    resetForm();
    await router.push(DashboardRoutes.DASHBOARD);
  },
  handleLaravelError: {
    translate: true,
  },
});

/**
 * Two-Factor Challenge Task
 *
 * Handles two-factor authentication code submission.
 */
const twoFactorTask = task.newTask({
  task: async () => {
    await twoFactorChallengeService.submitCode(twoFactorCode.value);

    // Fetch the session after successful 2FA
    await sessionStore.fetchSession();

    emit('success');
    resetForm();
    await router.push(DashboardRoutes.DASHBOARD);
  },
  handleLaravelError: {
    translate: true,
  },
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedIn'),
  },
});

/**
 * Recovery Code Task
 *
 * Handles recovery code submission for two-factor authentication.
 */
const recoveryCodeTask = task.newTask({
  task: async () => {
    await twoFactorChallengeService.submitRecoveryCode(recoveryCode.value);

    // Fetch the session after successful 2FA
    await sessionStore.fetchSession();

    emit('success');
    resetForm();
    await router.push(DashboardRoutes.DASHBOARD);
  },
  handleLaravelError: {
    translate: true,
  },
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedIn'),
  },
});

/**
 * Resets the form fields to their initial values.
 */
function resetForm() {
  email.value = '';
  password.value = '';
  twoFactorCode.value = '';
  recoveryCode.value = '';
  showTwoFactorChallenge.value = false;
  showRecoveryCodeInput.value = false;
  authForm.value?.reset();
  loginTask.reset();
  twoFactorTask.reset();
  recoveryCodeTask.reset();
}

/**
 * Handles form submission.
 */
function onSubmit() {
  void loginTask.run();
}

/**
 * Submit two-factor authentication code
 */
function submitTwoFactorCode() {
  if (!twoFactorCode.value || twoFactorCode.value.length !== 6) {
    twoFactorTask.errors.value.set(
      'code',
      i18n.t('auth.twoFactor.errors.invalidCode')
    );
    return;
  }

  void twoFactorTask.run();
}

/**
 * Submit recovery code
 */
function submitRecoveryCode() {
  if (!recoveryCode.value) {
    recoveryCodeTask.errors.value.set(
      'recovery_code',
      i18n.t('auth.twoFactor.errors.invalidRecoveryCode')
    );
    return;
  }

  void recoveryCodeTask.run();
}

/**
 * Toggle between code and recovery code input
 */
function toggleRecoveryCodeInput() {
  showRecoveryCodeInput.value = !showRecoveryCodeInput.value;
  twoFactorCode.value = '';
  recoveryCode.value = '';
  twoFactorTask.reset();
  recoveryCodeTask.reset();
}

/**
 * Go back to login form from two-factor challenge
 */
function backToLogin() {
  showTwoFactorChallenge.value = false;
  showRecoveryCodeInput.value = false;
  twoFactorCode.value = '';
  recoveryCode.value = '';
  twoFactorTask.reset();
  recoveryCodeTask.reset();
}

/**
 * OAuth login handler
 */
function loginWithOAuth(provider: string) {
  void sessionStore.loginWithOAuth(provider, quasar.platform.is.capacitor);
  selectedProvider.value = null;
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
  <!-- Regular Login Form -->
  <q-form
    v-if="!showTwoFactorChallenge"
    ref="authForm"
    @submit.prevent="onSubmit"
  >
    <!-- Oauth providers -->
    <div class="tw:mt-4 tw:my-8 tw:w-full">
      <q-select
        v-if="socialiteProviders?.length && socialiteProviders?.length > 0"
        :model-value="null"
        :options="socialiteProviders?.map(p => ({
          label: $t('auth.forms.oauth.logInWith', {
            provider: $t(`auth.forms.oauth.providers.${p}`)
          }),
          value: p
        }))"
        :label="$t('auth.forms.oauth.title')"
        :disable="loginTask.isActive.value"
        :loading="isOAuthRedirecting"
        @update:model-value="({ value }) => loginWithOAuth(value)"
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
      class="tw:mt-2"
      :task-errors="loginTask.errors.value"
    />

    <!-- Links -->
    <div class="tw:pt-4 tw:text-base tw:flex tw:gap-2 tw:justify-between">
      <span>
        <a
          class="underline cursor-pointer"
          @click="switchToSignup"
        >
          {{ $t('auth.forms.signup.link') }}
        </a>
      </span>

      <span>
        <a
          class="underline cursor-pointer"
          @click="switchToPasswordReset"
        >
          {{ $t('auth.forms.password.forgot') }}
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
        :loading="loginTask.isActive.value"
        :disabled="loginTask.isActive.value || isOAuthRedirecting"
      >
        {{ $t('auth.forms.login.button') }}
      </q-btn>
    </div>
  </q-form>

  <!-- Two-Factor Challenge -->
  <div v-else>
    <div class="tw:mb-6">
      <h3 class="text-h6 q-mb-sm">{{ $t('auth.twoFactor.title') }}</h3>
      <p class="text-body2 text-grey-7">
        {{ $t('auth.twoFactor.description') }}
      </p>
    </div>

    <!-- Authentication Code Input -->
    <q-form
      v-if="!showRecoveryCodeInput"
      @submit.prevent="submitTwoFactorCode"
    >
      <q-input
        v-model="twoFactorCode"
        :label="$t('auth.twoFactor.code')"
        mask="### ###"
        unmasked-value
        outlined
        autofocus
        class="tw:mb-4"
      />

      <!-- Errors -->
      <TaskErrors
        class="tw:mt-2"
        :task-errors="twoFactorTask.errors.value"
      />

      <!-- Recovery code link -->
      <div class="tw:mt-4 tw:text-center">
        <a
          class="underline cursor-pointer text-grey-7"
          @click="toggleRecoveryCodeInput"
        >
          {{ $t('auth.twoFactor.useRecoveryCode') }}
        </a>
      </div>

      <!-- Buttons -->
      <div class="tw:mt-6 tw:flex tw:justify-end tw:gap-4">
        <q-btn
          flat
          class="Button"
          @click="backToLogin"
        >
          {{ $t('common.buttons.back') }}
        </q-btn>

        <q-btn
          unelevated
          class="PrimaryButton tw:hover:bg-primary-600"
          type="submit"
          :loading="twoFactorTask.isActive.value"
          :disabled="twoFactorTask.isActive.value"
        >
          {{ $t('auth.twoFactor.verify') }}
        </q-btn>
      </div>
    </q-form>

    <!-- Recovery Code Input -->
    <q-form
      v-else
      @submit.prevent="submitRecoveryCode"
    >
      <q-input
        v-model="recoveryCode"
        :label="$t('auth.twoFactor.recoveryCode')"
        outlined
        autofocus
        class="tw:mb-4"
      />

      <!-- Errors -->
      <TaskErrors
        class="tw:mt-2"
        :task-errors="recoveryCodeTask.errors.value"
      />

      <!-- Back to code link -->
      <div class="tw:mt-4 tw:text-center">
        <a
          class="underline cursor-pointer text-grey-7"
          @click="toggleRecoveryCodeInput"
        >
          {{ $t('auth.twoFactor.useAuthenticatorCode') }}
        </a>
      </div>

      <!-- Buttons -->
      <div class="tw:mt-6 tw:flex tw:justify-end tw:gap-4">
        <q-btn
          flat
          class="Button"
          @click="backToLogin"
        >
          {{ $t('common.buttons.back') }}
        </q-btn>

        <q-btn
          unelevated
          class="PrimaryButton tw:hover:bg-primary-600"
          type="submit"
          :loading="recoveryCodeTask.isActive.value"
          :disabled="recoveryCodeTask.isActive.value"
        >
          {{ $t('auth.twoFactor.verify') }}
        </q-btn>
      </div>
    </q-form>
  </div>
</template>
