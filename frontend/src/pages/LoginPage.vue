<script lang="ts" setup>
/**
 * LandingPage.vue
 *
 * This component handles the landing page of the application.
 * TODO: For now, it's just a page to test the authentication flow.
 */
import { ref } from 'vue'
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue'
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
// import PasswordConfirmField from 'src/components/Form/PasswordConfirmField.vue';
import TaskErrors from 'src/components/Common/TaskErrors.vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore'
import { LaravelErrorHandler } from 'src/utils/errorUtil';
import type { User } from 'src/models/User';
import type { ErrorHandler } from 'src/types/task.types';

/**
 * Services
 */
const container = useContainer();
const sessionStore = useSessionStore();

/**
 * Refs
 */
const email = ref('');
const password = ref('');
const loginForm = ref<HTMLFormElement>();

/**
 * Login Task
 *
 * Handles user login and updates session state.
 */
const loginTask = container.task.newFrozenTask<User, { email: string, password: string }>({
  showNotification: {
    success: () => container.i18n.t('auth.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[
    LaravelErrorHandler(),
  ],
  successHandlers: () => {
    email.value = '';
    password.value = '';
  },
});

/**
 * Logout Task
 *
 * Logs the user out and clears session state.
 */
const logoutTask = container.task.newFrozenTask({
  showNotification: {
    success: () => container.i18n.t('auth.success.loggedOut'),
  },
  task: async () => await sessionStore.logout(),
});
</script>

<template>
  <div class="LandingPage">
    <div class="max-w-xl q-mx-auto">
      <h1 class="text-h3 text-weight-bold q-mb-xl">
        {{ $t('auth.forms.login.title') }}
      </h1>

      <div class="bg-gray-100 py-10">
        <h1 class="text-4xl font-bold">Tailwind Inside Quasar</h1>
        <q-btn
          color="primary"
          label="Quasar Button"
        />
      </div>
      <p>
        Email: quvel@quvel.app
        <br />
        Password: 12345678
      </p>

      <!-- User is already logged in -->
      <div
        v-if="sessionStore.isAuthenticated"
        class="q-mt-xl q-max-w-lg"
      >
        <p class="text-grey-3 text-h5">
          {{ $t('auth.forms.login.loggedInAs', { name: sessionStore.user?.name }) }}
        </p>

        <p class="text-grey-5 text-h6">{{ $t('auth.forms.common.email') }}: {{ sessionStore.user?.email }}</p>

        <q-btn
          color="negative"
          class="q-mt-md"
          :loading="logoutTask.state.value === 'active'"
          :disabled="logoutTask.state.value === 'active'"
          @click="logoutTask.run()"
        >
          {{ $t('auth.forms.login.logout') }}
        </q-btn>
      </div>

      <!-- Login Form -->
      <div
        v-else
        class="q-mt-xl login-box"
      >
        <q-form
          ref="loginForm"
          @submit.prevent="loginTask.run()"
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

          <!--
          <PasswordConfirmField
            v-model="passwordConfirmation"
            :error-message="(loginTask.errors.value.get('password_confirmation') as string) ?? ''"
            :error="loginTask.errors.value.has('password_confirmation')"
          /> -->

          <q-btn
            color="primary"
            class="q-mt-md"
            type="submit"
            :loading="loginTask.state.value === 'active'"
            :disabled="loginTask.state.value === 'active'"
          >
            {{ $t('auth.forms.login.button') }}
          </q-btn>
        </q-form>

        <TaskErrors
          class="q-mt-md"
          :task-errors="loginTask.errors.value"
        />
      </div>

      <div class="q-mt-xl">
        <p class="text-subtitle2">
          {{ $t('auth.forms.login.goTo') }}
          <RouterLink
            to="/welcome"
            class="text-primary"
          >
            {{ $t('auth.forms.login.welcomePage') }}
          </RouterLink>
        </p>
      </div>

      <div class="row justify-center q-gutter-md">
        <LanguageSwitcher />
        <ThemeSwitcher />
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.LandingPage {}
</style>
