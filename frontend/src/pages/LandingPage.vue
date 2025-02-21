<template>
  <q-page class="flex flex-center text-center">
    <div class="max-w-xl q-mx-auto">
      <h1 class="text-h3 text-weight-bold q-mb-xl">
        {{ $t('auth.forms.login.title') }}
      </h1>

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
          @click="sessionStore.logout()"
        >
          {{ $t('auth.forms.login.logout') }}
        </q-btn>
      </div>

      <div
        v-else
        class="q-mt-xl login-box"
      >
        <q-form @submit.prevent="login">
          <EmailField v-model="email" />
          <PasswordField v-model="password" />

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

<script lang="ts" setup>
import { ref } from 'vue'
import LanguageSwitcher from 'src/components/Misc/LanguageSwitcher.vue'
import { useSessionStore } from 'src/stores/sessionStore'
import ThemeSwitcher from 'src/components/Misc/ThemeSwitcher.vue';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';

const sessionStore = useSessionStore();
const email = ref('quvel@quvel.app');
const password = ref('123456');

function login(): void {
  if (email.value && password.value) {
    void sessionStore.login(email.value, password.value)
  }
}
</script>
