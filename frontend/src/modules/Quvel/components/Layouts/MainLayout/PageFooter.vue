<script setup lang="ts">
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
</script>

<template>
  <footer class="tw:mt-4 tw:py-6">
    <p class="tw:text-sm tw:text-center">
      {{ $t('createdBy') }}
      <a
        href="https://irv.codes"
        target="_blank"
        class="tw:underline tw:text-gray-600 tw:dark:text-gray-400 hover:tw:text-gray-800 tw:dark:hover:text-gray-200"
      >
        Irving Gomez
      </a>
    </p>

    <div class="tw:flex tw:justify-center tw:gap-4">
      <LanguageSwitcher />
    </div>
  </footer>
</template>
