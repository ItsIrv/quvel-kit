<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * Modular authentication dialog supporting login, signup, password reset, and MFA.
 */
import { computed, ref, watch, type Component } from 'vue';
import { QForm } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';

import LoginForm from 'src/modules/Auth/components/Forms/LoginForm.vue';
import SignupForm from 'src/modules/Auth/components/Forms/SignupForm.vue';
import PasswordResetForm from 'src/modules/Auth/components/Forms/PasswordResetForm.vue';

import RegistrationSuccessCard from 'src/modules/Auth/components/Cards/RegistrationSuccessCard.vue';
import PasswordResetSuccessCard from 'src/modules/Auth/components/Cards/PasswordResetSuccessCard.vue';

type AuthFormStep = 'login' | 'signup' | 'password-reset' | 'mfa';
type SuccessCardStep = 'registration' | 'password-reset' | false;

/**
 * Props & Emits
 */
defineProps<{ modelValue: boolean }>();
const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
}>();

/**
 * Stores
 */
const sessionStore = useSessionStore();

/**
 * State
 */
const activeStep = ref<AuthFormStep>('login');
const successStep = ref<SuccessCardStep>(false);

/**
 * Refs (current form ref)
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
    case 'mfa':
      return 'auth.forms.mfa.title';
    default:
      return '';
  }
});

/**
 * Computed - current form component
 */
const currentFormComponent = computed<Component | null>(() => {
  switch (activeStep.value) {
    case 'login':
      return LoginForm;
    case 'signup':
      return SignupForm;
    case 'password-reset':
      return PasswordResetForm;
    default:
      return null;
  }
});

/**
 * Methods
 */
function resetCurrentForm() {
  currentFormRef.value?.reset?.();
}

function switchStep(step: AuthFormStep) {
  activeStep.value = step;
  resetCurrentForm();
}

function handleBeforeShow() {
  successStep.value = false;
  switchStep('login');
}

function handleAuthSuccess() {
  emit('update:modelValue', false);
}

function handleRegistrationSuccess() {
  successStep.value = 'registration';
}

function handlePasswordResetSuccess() {
  successStep.value = 'password-reset';
}

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
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
    @before-show="handleBeforeShow"
  >
    <!-- Form or Success Card -->
    <q-card class="AuthDialog duration-1000 relative overflow-hidden">
      <h3 class="text-h4 font-semibold text-gray-900 dark:text-white">
        <QuvelKit>{{ $t(stepTitle) }}</QuvelKit>
      </h3>

      <!-- Forms -->
      <transition mode="out-in">
        <component
          v-if="!successStep && currentFormComponent"
          :is="currentFormComponent"
          ref="currentFormRef"
          :key="activeStep"
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
