<template>
  <q-page class="flex flex-center text-center">
    <div class="max-w-3xl">
      <h1 class="text-h3 text-weight-bold q-mb-md">
        Welcome to <span class="text-primary">QuVel Kit</span>
      </h1>

      <p class="text-subtitle1 text-grey-5 q-mb-xl">
        A full-stack hybrid starter kit for Laravel & Quasar.

        <br />

        Built for modern web, mobile, and desktop applications.
      </p>

      <div class="q-gutter-md row justify-center">
        <q-btn
          color="secondary"
          size="md"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/"
        >
          GitHub Repository
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
        >
          Documentation
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://api.quvel.127.0.0.1.nip.io"
        >
          API Playground
        </q-btn>
        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
        >
          Vitest UI
        </q-btn>
        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage-api.quvel.127.0.0.1.nip.io"
        >
          Laravel Coverage
        </q-btn>
        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="http://localhost:8080"
        >
          Traefik Dashboard
        </q-btn>
        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          to="/"
        >
          Frontend Playground
        </q-btn>
      </div>
    </div>
  </q-page>
</template>

<style lang="scss" scoped></style>
