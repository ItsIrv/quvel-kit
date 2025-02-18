<template>
  <q-page class="hero-container">
    <div class="content q-pa-md text-center">
      <h1 class="text-h3 text-weight-bold q-mb-md">
        Welcome to <span class="text-primary">QuVel Kit</span>
      </h1>

      <p class="text-subtitle1 text-gray-200 q-mb-lg">
        A full-stack hybrid starter kit for Laravel & Quasar, built for modern web, mobile, and desktop applications.
      </p>

      <div class="q-gutter-md row justify-center">
        <q-btn
          color="primary"
          size="lg"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/"
        >
          GitHub Repository
        </q-btn>
        <q-btn
          color="secondary"
          size="lg"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
        >
          Documentation
        </q-btn>
      </div>

      <div class="q-mt-lg q-gutter-md row justify-center">
        <q-btn
          color="accent"
          size="md"
          class="q-px-md"
          unelevated
          href="https://api.quvel.127.0.0.1.nip.io"
        >
          API Playground
        </q-btn>
        <q-btn
          color="accent"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
        >
          Vitest UI
        </q-btn>
        <q-btn
          color="accent"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage-api.quvel.127.0.0.1.nip.io"
        >
          Laravel Coverage
        </q-btn>
        <q-btn
          color="accent"
          size="md"
          class="q-px-md"
          unelevated
          href="http://localhost:8080"
        >
          Traefik Dashboard
        </q-btn>
      </div>
    </div>
  </q-page>
</template>

<script lang="ts" setup>
defineOptions({
  preFetch() {
    console.log(1)
  }
})
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
</style>
