<script lang="ts" setup>
import { ref } from 'vue'
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue'
import { useSessionStore } from 'src/stores/sessionStore'
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import { useContainer } from 'src/services/ContainerService';
import { LaravelErrorHandler } from 'src/utils/taskUtil';
import type { ErrorHandler } from 'src/types/task.types';
import type { User } from 'src/models/User';

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
const loginTask = container.task.newTask<User, { email: string, password: string }>({
  shouldRun: async () => await loginForm.value?.validate(),
  showLoading: true,
  showNotification: {
    error: false,
    success: container.i18n.t('auth.success.loggedIn'),
  },
  taskPayload: () => ({ email: email.value, password: password.value }),
  task({ email, password }) {
    return sessionStore.login(email, password);
  },
  errorHandlers: <ErrorHandler[]>[
    LaravelErrorHandler(),
    {
      key: 'status',
      matcher: (status: number): boolean => status === 400,
      callback(): void {
        console.log('Bad Request')
      }
    }
  ],
  successHandlers: () => {
    email.value = '';
    password.value = '';
  },
});

const logoutTask = container.task.newTask({
  showLoading: true,
  showNotification: {
    error: false,
    success: container.i18n.t('auth.success.loggedOut'),
  },
  task() {
    return sessionStore.logout();
  },
});
</script>

<template>
  <q-page class="flex flex-center text-center">
    <div class="max-w-xl q-mx-auto">
      <h1 class="text-h3 text-weight-bold q-mb-xl">
        {{ $t('auth.forms.login.title') }}
      </h1>

      <p>
        Email: quvel@quvel1.kit
        <br />
        Password: 12345678
      </p>

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
          @click="logoutTask.run()"
        >
          {{ $t('auth.forms.login.logout') }}
        </q-btn>
      </div>

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
            :error-message="(loginTask.errors.value.email as any)?.[0] ?? ''"
            :error="!!loginTask.errors.value.email"
          />

          <PasswordField
            v-model="password"
            :error-message="(loginTask.errors.value.password as any)?.[0] ?? ''"
            :error="!!loginTask.errors.value.password"
          />

          <q-btn
            color="primary"
            class="q-mt-md"
            type="submit"
          >
            {{ $t('auth.forms.login.button') }}
          </q-btn>
        </q-form>
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
  </q-page>
</template>
