<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * A dialog for logging in and signing up.
 */
import { ref, computed } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import type { ErrorHandler } from 'src/modules/Core/types/task.types';
import QuvelKit from 'src/modules/Quvel/components/Common/QuvelKit.vue';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import TaskErrors from 'src/modules/Core/components/Common/TaskErrors.vue';
import PasswordConfirmField from 'src/modules/Auth/components/Form/PasswordConfirmField.vue';
import NameField from 'src/modules/Auth/components/Form/NameField.vue';
import SlowExpand from 'src/modules/Core/components/Transitions/SlowExpand.vue';
import BackInOutUp from 'src/modules/Core/components/Transitions/BackInOutUp.vue';
import { useQuasar } from 'quasar';
import { watch } from 'vue';

type AuthDialogType = 'login' | 'signup';

/**
 * Props
 */
defineProps<{ modelValue: boolean }>();

/**
 * Emits
 */
const $emit = defineEmits(['update:modelValue']);

/**
 * Services
 */
const container = useContainer();
const sessionStore = useSessionStore();
const quasar = useQuasar();

/**
 * Refs
 */
const email = ref('');
const name = ref('');
const password = ref('');
const passwordConfirm = ref('');
const successStep = ref<false | AuthDialogType>(false);
const isOAuthRedirecting = ref(false);
const dialogType = ref<AuthDialogType>('login');
const authForm = ref<HTMLFormElement>();

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = container.task.newTask({
  showNotification: {
    success: () => container.i18n.t('auth.status.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel()],
  successHandlers: () => {
    $emit('update:modelValue', false);
    resetForms();
  },
});

/**
 * Signup Task
 *
 * Handles user signup.
 */
const signupTask = container.task.newTask({
  showNotification: {
    success: () => container.i18n.t('auth.status.success.signedUp'),
  },
  task: async () => await sessionStore.signUp(email.value, password.value, name.value),
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel()],
  successHandlers: () => {
    successStep.value = 'signup';
    resetForms();
  },
});

/**
 * Indicates whether the login or signup task is currently active.
 */
const isBusy = computed(
  () => loginTask.isActive.value || signupTask.isActive.value || isOAuthRedirecting.value,
);

/**
 * Indicates whether the current dialog is the login dialog.
 */
const isLogin = computed(() => dialogType.value === 'login');

/**
 * Indicates whether the current dialog is the signup dialog.
 */
const isSignup = computed(() => dialogType.value === 'signup');

/**
 * Resets all form fields to their initial values.
 */
function resetForms() {
  email.value = '';
  password.value = '';
  passwordConfirm.value = '';
  name.value = '';

  authForm.value?.reset();
  loginTask.reset();
  signupTask.reset();
}

/**
 * Sets the dialog type.
 *
 * Resets all form fields to their initial values.
 */
function setDialogType(type: AuthDialogType) {
  dialogType.value = type;
  resetForms();
}

/**
 * Handles form submission based on the dialog type.
 */
function onAuthFormSubmit() {
  if (isLogin.value) {
    void loginTask.run();
  } else {
    void signupTask.run();
  }
}

/**
 * Handles the before-show event of the dialog.
 */
function onBeforeShow() {
  resetForms();
  setDialogType('login');
}

function loginWithOAuth(provider: string) {
  void sessionStore.loginWithOAuth(provider, quasar.platform.is.capacitor);

  isOAuthRedirecting.value = true;

  setTimeout(() => {
    isOAuthRedirecting.value = false;
  }, 5000);
}

watch(
  () => sessionStore.user,
  () => {
    if (sessionStore.user) {
      $emit('update:modelValue', false);
    }
  },
);
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    @before-show="onBeforeShow"
  >
    <q-card
      v-if="!successStep"
      class="AuthDialog duration-1000 relative overflow-hidden"
    >
      <!-- Title -->
      <h3 class="text-h4 font-semibold text-gray-900 dark:text-white">
        <QuvelKit>
          {{ $t(isLogin ? 'auth.forms.login.title' : 'auth.forms.signup.title') }}
        </QuvelKit>
      </h3>

      <BackInOutUp>
        <!-- Oauth providers -->
        <div
          v-if="isLogin"
          class="grid grid-cols-2 gap-2 mt-4 mb-2"
        >
          <q-btn
            class="GenericBorder AccentGradient Button"
            :label="$t('auth.forms.oauth.logInWith', { provider: $t('auth.forms.oauth.google') })"
            unelevated
            :disable="isBusy"
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
      </BackInOutUp>

      <!-- Form -->
      <q-form
        class="my-4"
        ref="authForm"
        @submit.prevent="onAuthFormSubmit"
      >
        <EmailField
          v-model="email"
          :error-message="loginTask.errors.value.get('email') ?? ''"
          :error="loginTask.errors.value.has('email')"
        />

        <BackInOutUp>
          <NameField
            v-if="isSignup"
            v-model="name"
            :error-message="loginTask.errors.value.get('name') ?? ''"
            :error="loginTask.errors.value.has('name')"
          />
        </BackInOutUp>

        <PasswordField
          v-model="password"
          :error-message="loginTask.errors.value.get('password') ?? ''"
          :error="loginTask.errors.value.has('password')"
        />

        <SlowExpand>
          <div
            v-if="isSignup"
            class="overflow-hidden"
          >
            <PasswordConfirmField
              v-model="passwordConfirm"
              :password-value="password"
            />
          </div>
        </SlowExpand>

        <!-- Errors -->
        <TaskErrors
          class="mt-2"
          :task-errors="isLogin ? loginTask.errors.value : signupTask.errors.value"
        />

        <!-- Links -->
        <div class="pt-4 text-base">
          <span v-if="isLogin">
            {{ $t('auth.forms.signup.link') }}

            <a
              class="underline cursor-pointer"
              @click="setDialogType('signup')"
            >
              {{ $t('auth.forms.signup.button') }}
            </a>
          </span>

          <span v-if="isSignup">
            {{ $t('auth.forms.signup.link') }}

            <a
              class="underline cursor-pointer"
              @click="setDialogType('login')"
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
            @click="$emit('update:modelValue', false)"
          >
            {{ $t('common.buttons.cancel') }}
          </q-btn>

          <q-btn
            unelevated
            class="PrimaryButton hover:bg-primary-600"
            type="submit"
            :loading="loginTask.isActive.value || signupTask.isActive.value"
            :disabled="isBusy"
          >
            {{ $t(`auth.forms.${dialogType}.button`) }}
          </q-btn>
        </div>
      </q-form>
    </q-card>

    <q-card
      class="AuthDialog duration-1000 relative overflow-hidden"
      v-if="successStep === 'signup'"
    >
      <q-card-section class="flex flex-col items-center">
        <q-icon
          name="eva-email-outline"
          color="green"
          size="6em"
          class="mb-4"
        />

        <div class="text-h6">
          {{ $t('auth.status.success.signedUp') }}
        </div>

        <div class="text-base mb-4">
          {{ $t('auth.status.success.checkYourEmail') }}
        </div>

        <q-btn
          unelevated
          class="PrimaryButton hover:bg-primary-600"
          @click="successStep = false"
        >
          {{ $t('common.buttons.close') }}
        </q-btn>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>
