<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * A dialog for logging in and signing up.
 */
import { ref, computed } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore';
import type { ErrorHandler } from 'src/types/task.types';
import QuvelKit from 'src/components/Common/QuvelKit.vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import TaskErrors from 'src/components/Common/TaskErrors.vue';
import PasswordConfirmField from 'src/components/Form/PasswordConfirmField.vue';
import NameField from 'src/components/Form/NameField.vue';
import SlowExpand from 'src/components/Transitions/SlowExpand.vue';
import BackInOutUp from '../Transitions/BackInOutUp.vue';

type AuthDialogType = 'login' | 'signup';

/**
 * Props
 */
defineProps<{ modelValue: boolean }>();

/**
 * Emits
 */
const emit = defineEmits(['update:modelValue']);

/**
 * Services
 */
const container = useContainer();
const sessionStore = useSessionStore();

/**
 * Refs
 */
const email = ref('');
const name = ref('');
const password = ref('');
const passwordConfirm = ref('');
const dialogType = ref<AuthDialogType>('login');
const authForm = ref<HTMLFormElement>();

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = container.task.newFrozenTask({
  showNotification: {
    success: () => container.i18n.t('auth.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel()],
  successHandlers: () => {
    emit('update:modelValue', false);
    resetForms();
  },
});

/**
 * Signup Task
 *
 * Handles user signup.
 */
const signupTask = container.task.newFrozenTask({
  showNotification: {
    success: () => container.i18n.t('auth.success.signedUp'),
  },
  task: async () => await sessionStore.signUp(email.value, password.value, name.value),
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel()],
  successHandlers: () => {
    emit('update:modelValue', false);
    resetForms();
  },
});

/**
 * Indicates whether the login or signup task is currently active.
 */
const isBusy = computed(() => loginTask.isActive.value || signupTask.isActive.value);

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
  void sessionStore.loginWithOAuth(provider);
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    @before-show="onBeforeShow"
  >
    <q-card class="AuthDialog duration-1000 relative overflow-hidden">
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
          :error-message="(loginTask.errors.value.get('email') as string) ?? ''"
          :error="loginTask.errors.value.has('email')"
        />

        <BackInOutUp>
          <NameField
            v-if="isSignup"
            v-model="name"
            :error-message="(loginTask.errors.value.get('name') as string) ?? ''"
            :error="loginTask.errors.value.has('name')"
          />
        </BackInOutUp>

        <PasswordField
          v-model="password"
          :error-message="(loginTask.errors.value.get('password') as string) ?? ''"
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
            :loading="isBusy"
            :disabled="isBusy"
          >
            {{ $t(`auth.forms.${dialogType}.button`) }}
          </q-btn>
        </div>
      </q-form>
    </q-card>
  </q-dialog>
</template>
