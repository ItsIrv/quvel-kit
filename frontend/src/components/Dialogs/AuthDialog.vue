<script lang="ts" setup>
import { ref, defineProps, defineEmits } from 'vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import TaskErrors from 'src/components/Common/TaskErrors.vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore';
import { LaravelErrorHandler } from 'src/utils/errorUtil';
import type { User } from 'src/models/User';
import type { ErrorHandler } from 'src/types/task.types';
import QuvelKit from '../Common/QuvelKit.vue';

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
const password = ref('12345678');
const loginForm = ref<HTMLFormElement>();

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
</script>

<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="AuthDialog">
      <h3 class="text-h4 font-semibold text-gray-900 dark:text-white">
        <QuvelKit>
          {{ $t('auth.forms.login.title') }}
        </QuvelKit>
      </h3>

      <q-form
        @submit.prevent="loginTask.run()"
        ref="loginForm"
        class="mt-6"
      >
        <EmailField
          v-model="email"
          :error-message="(loginTask.errors.value.get('email') as string) ?? ''"
          :error="loginTask.errors.value.has('email')"
        />

        <PasswordField
          v-model="password"
          :error-message="(loginTask.errors.value.get('password') as string) ?? ''"
          :error="loginTask.errors.value.has('password')"
        />

        <TaskErrors
          class="mt-4"
          :task-errors="loginTask.errors.value"
        />

        <div class="pt-4">
          Need an account? <a href="/register">Register</a>
        </div>

        <div class="mt-6 flex justify-end gap-4">
          <q-btn
            flat
            :label="$t('common.buttons.cancel')"
            class="Button"
            @click="$emit('update:modelValue', false)"
          />

          <q-btn
            unelevated
            class="PrimaryButton hover:bg-primary-600"
            type="submit"
            :loading="loginTask.state.value === 'active'"
            :disabled="loginTask.state.value === 'active'"
          >
            {{ $t('auth.forms.login.button') }}
          </q-btn>
        </div>
      </q-form>
    </div>
  </q-dialog>
</template>
