<template>
  <q-page class="hero-container">
    <div class="content q-pa-md text-center">
      <h1 class="text-h3 text-weight-bold q-mb-md">
        Elevate Your Development with <span class="text-primary">QuVel Kit</span>
      </h1>
      <p class="text-subtitle1 q-mb-lg">
        A powerful, hybrid starter kit for Laravel & Quasar, designed for speed, scalability, and seamless deployment.
      </p>

      <q-btn
        color="primary"
        size="lg"
        class="q-px-lg"
        unelevated
        href="https://github.com/ItsIrv/quvel-kit/"
      >
        Get Started
      </q-btn>

      <p class="text-caption q-mt-lg text-grey-4">
        Built for developers, by developers.
      </p>
    </div>
  </q-page>
</template>

<script lang="ts" setup>
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
  max-width: 600px;
}

.text-primary {
  color: #4f46e5;
}
</style>
