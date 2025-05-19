<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * Modular authentication dialog supporting login, signup, password reset, and MFA.
 */
import { computed, ref, watch, defineAsyncComponent, type Component } from 'vue';
import { QForm } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';

import { useContainer } from 'src/modules/Core/composables/useContainer';
import { resetPasswordTokenSchema } from 'src/modules/Auth/validators/authValidators';
import { useUrlQueryHandler } from 'src/modules/Core/composables/useUrlQueryHandler';

/**
 * Types
 */
type AuthFormStep = 'login' | 'signup' | 'password-reset' | 'password-reset-token' | 'mfa';
type SuccessCardStep = 'registration' | 'password-reset' | false;

/*
 * Components
 */
const LoginForm = defineAsyncComponent(() => import('src/modules/Auth/components/Forms/LoginForm.vue'));
const SignupForm = defineAsyncComponent(() => import('src/modules/Auth/components/Forms/SignupForm.vue'));
const PasswordResetForm = defineAsyncComponent(() => import('src/modules/Auth/components/Forms/PasswordResetForm.vue'));
const PasswordResetTokenForm = defineAsyncComponent(() => import('src/modules/Auth/components/Forms/PasswordResetTokenForm.vue'));
const RegistrationSuccessCard = defineAsyncComponent(() => import('src/modules/Auth/components/Cards/RegistrationSuccessCard.vue'));
const PasswordResetSuccessCard = defineAsyncComponent(() => import('src/modules/Auth/components/Cards/PasswordResetSuccessCard.vue'));

/**
 * Props & Emits
 */
defineProps<{ modelValue: boolean }>();
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'open'): void;
}>();

/**
 * Stores
 */
const sessionStore = useSessionStore();

/**
 * Services
 */
const { validation, i18n } = useContainer();

/**
 * State
 */
const activeStep = ref<AuthFormStep>('login');
const successStep = ref<SuccessCardStep>(false);
const resetToken = ref<string>('');
const isValidToken = ref<true | string | null>(null);

/**
 * Refs
 */
const currentFormRef = ref<InstanceType<typeof QForm> | null>(null);

/**
 * Computed
 */
const stepTitle = computed(() => {
  switch (activeStep.value) {
    case 'login':
      return 'auth.forms.login.title';
    case 'signup':
      return 'auth.forms.signup.title';
    case 'password-reset':
      return 'auth.forms.password.title';
    case 'password-reset-token':
      return 'auth.forms.passwordReset.title';
    case 'mfa':
      return 'auth.forms.mfa.title';
    default:
      return '';
  }
});

/**
 * Current form component
 */
const currentFormComponent = computed<Component | null>(() => {
  switch (activeStep.value) {
    case 'login':
      return LoginForm;
    case 'signup':
      return SignupForm;
    case 'password-reset':
      return PasswordResetForm;
    case 'password-reset-token':
      return PasswordResetTokenForm;
    default:
      return null;
  }
});

/**
 * Props for the current form
 */
const currentFormProps = computed(() => {
  if (activeStep.value === 'password-reset-token') {
    return {
      token: resetToken.value,
    };
  }
  return {};
});

/**
 * Methods
 */
function resetCurrentForm() {
  currentFormRef.value?.reset?.();
}

/**
 * Switches to the specified step.
 */
function switchStep(step: AuthFormStep) {
  activeStep.value = step;
  resetCurrentForm();
}

/**
 * Handles the before-show event of the dialog.
 */
function handleBeforeShow() {
  successStep.value = false;

  if (isValidToken.value) {
    switchStep('password-reset-token');
  } else {
    switchStep('login');
  }
}

/**
 * Handles the authentication success event.
 */
function handleAuthSuccess() {
  emit('update:modelValue', false);
}

/**
 * Handles the registration success event.
 */
function handleRegistrationSuccess() {
  successStep.value = 'registration';
}

/**
 * Handles the password reset success event.
 */
function handlePasswordResetSuccess() {
  successStep.value = 'password-reset';
}

/**
 * Handles the close success card event.
 */
function handleCloseSuccessCard() {
  successStep.value = false;
  switchStep('login');
}


/**
 * Effects
 */
watch(
  () => sessionStore.user,
  (user) => {
    if (user) emit('update:modelValue', false);
  },
);

/**
 * Check for password reset token on mount
 */
useUrlQueryHandler({
  params: ['form', 'token'],
  validate: (params) =>
    validation.validateFirstError(
      params,
      resetPasswordTokenSchema(),
      i18n.t('auth.forms.passwordReset.token')
    ) === true,
  onMatch: ({ token }) => {
    isValidToken.value = true;
    resetToken.value = token || '';
    activeStep.value = 'password-reset-token';

    emit('open');
  },
});
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    @before-show="handleBeforeShow"
  >
    <!-- Form or Success Card -->
    <q-card
      class="DialogGradient GenericBorder MainTransition duration-1000 relative overflow-hidden tw:p-6 tw:w-96 tw:rounded-xl tw:shadow-sm"
    >
      <h3 class="tw:text-h4 tw:font-semibold tw:text-gray-900 tw:dark:text-white">
        <QuvelKit>{{ $t(stepTitle) }}</QuvelKit>
      </h3>

      <!-- Forms -->
      <transition mode="out-in">
        <component
          v-if="!successStep && currentFormComponent"
          :is="currentFormComponent"
          ref="currentFormRef"
          :key="activeStep"
          v-bind="currentFormProps"
          @success="handleAuthSuccess"
          @switch-form="switchStep"
          @registration-success="handleRegistrationSuccess"
          @reset-success="handlePasswordResetSuccess"
        />
      </transition>

      <!-- Success Cards -->
      <transition mode="out-in">
        <RegistrationSuccessCard
          v-if="successStep === 'registration'"
          @close="handleCloseSuccessCard"
        />

        <PasswordResetSuccessCard
          v-else-if="successStep === 'password-reset'"
          @close="handleCloseSuccessCard"
        />
      </transition>
    </q-card>
  </q-dialog>
</template>
