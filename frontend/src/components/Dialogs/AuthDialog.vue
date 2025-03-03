<script lang="ts" setup>
/**
 * AuthDialog.vue
 *
 * A dialog for logging in and signing up.
 */
import { ref, computed } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore';
import type { User } from 'src/models/User';
import type { ErrorHandler } from 'src/types/task.types';
import QuvelKit from 'src/components/Common/QuvelKit.vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import TaskErrors from 'src/components/Common/TaskErrors.vue';
import PasswordConfirmField from 'src/components/Form/PasswordConfirmField.vue';
import NameField from 'src/components/Form/NameField.vue';
import SlowExpand from 'src/components/Transition/SlowExpand.vue';

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
  errorHandlers: <ErrorHandler[]>[container.task.errorHandlers.Laravel()],
  successHandlers: () => {
    resetForms();
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
      <!-- Title -->
      <h3 class="text-h4 font-semibold text-gray-900 dark:text-white">
        <QuvelKit>
          {{ $t(dialogType === 'login' ? 'auth.forms.login.title' : 'auth.forms.signup.title') }}
        </QuvelKit>
      </h3>

      <!-- Form -->
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

        <!-- Errors -->
        <TaskErrors
          class="mt-4"
          :task-errors="dialogType === 'login' ? loginTask.errors.value : signupTask.errors.value"
        />

        <!-- Links -->
        <div class="pt-4 text-base">
          <span v-if="dialogType === 'login'">
            {{ $t('auth.forms.signup.link') }}

            <a
              class="underline cursor-pointer"
              @click="setDialogType('signup')"
            >
              {{ $t('auth.forms.signup.button') }}
            </a>
          </span>


          <span v-if="dialogType === 'signup'">
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
