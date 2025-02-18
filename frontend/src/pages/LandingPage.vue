<template>
  <q-page class="hero-container">
    <div class="content q-pa-md text-center">
      <h1 class="text-h3 text-weight-bold q-mb-xl">
        Welcome to <span class="text-primary">QuVel Kit</span>
      </h1>

      <div
        v-if="sessionStore.isAuthenticated"
        class="q-mt-xl"
      >
        <p class="text-gray-200">Logged in as <strong>{{ sessionStore.user?.name }}</strong></p>
        <p class="text-gray-400">Email: {{ sessionStore.user?.email }}</p>
        <q-btn
          color="negative"
          class="q-mt-md"
          @click="() => sessionStore.logout(api)"
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
        <RouterLink
          to="/welcome"
          class="text-primary"
        >
          Go to Welcome Page.
        </RouterLink>
      </div>
    </div>
  </q-page>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useSessionStore } from 'stores/session-store'
import { createApi } from 'boot/axios'

const api = createApi()
const sessionStore = useSessionStore()
const email = ref('quvel@quvel.app')
const password = ref('123456')

async function login() {
  if (email.value && password.value) {
    await sessionStore.login(api, email.value, password.value)
  }
}
</script>

<style scoped>
.hero-container {
  background: linear-gradient(135deg, #0f172a, #1e3a8a);
  color: white;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.content {
  max-width: 700px;
}

.text-primary {
  color: #4f46e5;
}

.text-gray-200 {
  color: rgba(255, 255, 255, 0.85);
}

.text-gray-400 {
  color: rgba(255, 255, 255, 0.6);
}

.login-box {
  max-width: 300px;
  margin: 0 auto;
}
</style>
