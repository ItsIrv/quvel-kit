<template>
  <!-- TODO: For now this is an auth page. When Auth is rolled out the final landing page 
    should be a header signaling whether the user is logged in or not, and a mix of public
    pre-fetched data to simulate a real products landing, not just an auth portal.
  -->
  <q-page class="flex flex-center text-center">
    <div class="max-w-xl q-mx-auto">
      <h1 class="text-h3 text-weight-bold q-mb-xl">
        Login to <span class="text-primary">QuVel Kit</span>
      </h1>

      <div
        v-if="sessionStore.isAuthenticated"
        class="q-mt-xl q-max-w-lg"
      >
        <p class="text-grey-3 text-h5">Logged in as <strong>{{ sessionStore.user?.name }}</strong></p>
        <p class="text-grey-5 text-h6">Email: {{ sessionStore.user?.email }}</p>
        <q-btn
          color="negative"
          class="q-mt-md"
          @click="() => sessionStore.logout()"
        >Logout</q-btn>
      </div>

      <div
        v-else
        class="q-mt-xl login-box"
      >
        <q-form @submit.prevent="login">
          <q-input
            v-model="email"
            name="email"
            filled
            dark
            label="Email"
            class="q-mb-md"
            type="email"
            autocomplete="email"
            required
          />
          <q-input
            v-model="password"
            name="password"
            filled
            dark
            type="password"
            label="Password"
            class="q-mb-md"
            autocomplete="current-password"
            required
          />
          <q-btn
            color="primary"
            class="q-mt-md"
            type="submit"
          >Login</q-btn>
        </q-form>
      </div>

      <div class="q-mt-xl">
        <p class="text-grey-5 text-subtitle2">
          Go to
          <RouterLink
            to="/welcome"
            class="text-primary"
          >
            Welcome</RouterLink>
          Page.
        </p>
      </div>
    </div>
  </q-page>
</template>

<style lang="scss" scoped></style>

<script lang="ts" setup>
import { ref } from 'vue'
import { useSessionStore } from 'src/stores/sessionStore'

const sessionStore = useSessionStore();
const email = ref('quvel@quvel.app');
const password = ref('123456');

function login(): void {
  if (email.value && password.value) {
    void sessionStore.login(email.value, password.value)
  }
}
</script>
