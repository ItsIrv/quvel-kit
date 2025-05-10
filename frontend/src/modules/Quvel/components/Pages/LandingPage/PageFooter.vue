<script setup lang="ts">
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';
</script>

<template>
  <footer class="mt-4 py-6">
    <p class="text-sm text-center">
      {{ $t('createdBy') }}
      <a
        href="https://irv.codes"
        target="_blank"
        class="underline text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
      >
        Irving Gomez
      </a>
    </p>

    <div class="flex justify-center gap-4">
      <ThemeSwitcher />
      <LanguageSwitcher />
    </div>
  </footer>
</template>
