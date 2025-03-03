<script setup lang="ts">
// TODO: Keeping this file for the test suite example until I have time to make real tests.
import { useMetaConfig } from 'src/composables/useMetaConfig';

useMetaConfig('Welcome');
</script>

<template>
  <q-page class="flex flex-center text-center">
    <div class="max-w-3xl">
      <h1 class="text-h3 text-weight-bold q-mb-md">
        {{ $t('welcome.title', { appName: 'QuVel Kit' }) }}
      </h1>

      <p class="text-subtitle1 text-grey-5 q-mb-xl">
        {{ $t('welcome.description') }}
        <br />
        {{ $t('welcome.subtitle') }}
      </p>

      <div class="q-gutter-md row justify-center">
        <q-btn
          color="secondary"
          size="md"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/"
        >
          {{ $t('welcome.links.github') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-lg"
          unelevated
          href="https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md"
        >
          {{ $t('welcome.links.docs') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://api.quvel.127.0.0.1.nip.io"
        >
          {{ $t('welcome.links.api') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage.quvel.127.0.0.1.nip.io/__vitest__/"
        >
          {{ $t('welcome.links.vitest') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="https://coverage-api.quvel.127.0.0.1.nip.io"
        >
          {{ $t('welcome.links.coverage') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          href="http://localhost:8080"
        >
          {{ $t('welcome.links.traefik') }}
        </q-btn>

        <q-btn
          color="secondary"
          size="md"
          class="q-px-md"
          unelevated
          to="/"
        >
          {{ $t('welcome.links.frontend') }}
        </q-btn>
      </div>
    </div>
  </q-page>
</template>
