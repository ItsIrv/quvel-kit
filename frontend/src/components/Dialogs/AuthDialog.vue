<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * A dialog for logging in and signing up.
 */
import { ref, computed } from 'vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import TaskErrors from 'src/components/Common/TaskErrors.vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore';
import { LaravelErrorHandler } from 'src/utils/errorUtil';
import type { User } from 'src/models/User';
import type { ErrorHandler } from 'src/types/task.types';
import QuvelKit from '../Common/QuvelKit.vue';
import PasswordConfirmField from '../Form/PasswordConfirmField.vue';
import NameField from '../Form/NameField.vue';
import SlowExpand from '../Transition/SlowExpand.vue';

/**
 * Props
 */
defineProps<{ modelValue: boolean }>();

/**
 * Emits
 */
const emit = defineEmits(['update:modelValue']);

/**
 * Composables
 */
const container = useContainer();
const sessionStore = useSessionStore();

/**
 * Refs
 */
const email = ref('quvel@quvel.app');
const name = ref('Quvel User');
const password = ref('12345678');
const passwordConfirm = ref('12345678');
const dialogType = ref<'login' | 'signup'>('login');
const authForm = ref<HTMLFormElement>();

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = container.task.newFrozenTask<User, { email: string; password: string }>({
  showNotification: {
    success: () => container.i18n.t('auth.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[LaravelErrorHandler()],
  successHandlers: () => {
    email.value = '';
    password.value = '';
    emit('update:modelValue', false);
  },
});

/**
 * Signup Task
 *
 * Handles user signup.
 */
const signupTask = container.task.newFrozenTask<void, { email: string; password: string }>({
  showNotification: {
    success: () => container.i18n.t('auth.success.signedUp'),
  },
  task: async () => await sessionStore.signUp(email.value, password.value, name.value),
  errorHandlers: <ErrorHandler[]>[LaravelErrorHandler()],
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

function setDialogType(type: 'login' | 'signup') {
  dialogType.value = type;
  resetForms();
}

/**
 * Handles form submission based on the dialog type.
 */
function onAuthFormSubmit() {
  if (dialogType.value === 'login') {
    void loginTask.run();
  } else {
    void signupTask.run();
  }
}
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    @before-show="resetForms(); setDialogType('login')"
  >
    <q-card class="AuthDialog duration-1000 relative overflow-hidden">
      <h3 class="text-h4 font-semibold text-gray-900 dark:text-white">
        <QuvelKit>
          {{ $t(dialogType === 'login' ? 'auth.forms.login.title' : 'auth.forms.signup.title') }}
        </QuvelKit>
      </h3>

      <q-form
        @submit.prevent="onAuthFormSubmit"
        ref="authForm"
      >
        <EmailField
          v-model="email"
          :error-message="(loginTask.errors.value.get('email') as string) ?? ''"
          :error="loginTask.errors.value.has('email')"
        />

        <SlowExpand>
          <NameField
            v-if="dialogType === 'signup'"
            v-model="name"
            :error-message="(loginTask.errors.value.get('name') as string) ?? ''"
            :error="loginTask.errors.value.has('name')"
          />
        </SlowExpand>

        <PasswordField
          v-model="password"
          :error-message="(loginTask.errors.value.get('password') as string) ?? ''"
          :error="loginTask.errors.value.has('password')"
        />

        <SlowExpand>
          <div
            v-if="dialogType === 'signup'"
            class="overflow-hidden"
          >
            <PasswordConfirmField
              v-model="passwordConfirm"
              :password-value="password"
            />
          </div>
        </SlowExpand>

        <TaskErrors
          class="mt-4"
          :task-errors="dialogType === 'login' ? loginTask.errors.value : signupTask.errors.value"
        />

        <div class="pt-4 text-base">
          <a
            v-if="dialogType === 'login'"
            @click="setDialogType('signup')"
          >
            {{ $t('auth.forms.signup.link') }}
          </a>
          <a
            v-if="dialogType === 'signup'"
            @click="setDialogType('login')"
          >
            {{ $t('auth.forms.login.link') }}
          </a>
        </div>

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
