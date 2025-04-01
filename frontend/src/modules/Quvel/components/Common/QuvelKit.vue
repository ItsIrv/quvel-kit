<script lang="ts" setup>
import { useContainer } from 'src/modules/Core/composables/useContainer';

const configService = useContainer().config;

defineProps({
  link: {
    type: Boolean,
    required: false,
    default: false,
  },
});
</script>

<template>
  <a v-if="link" :href="configService.get('app_url')">
    <span class="QuvelKit text-2xl font-bold text-gray-900 dark:text-white">
      <!-- Tight spacing on purpose; prevents space in text.-->
      <span class="text-blue-500">
        {{ configService.get('app_name') }}
      </span>

      Kit
      <slot></slot>
    </span>
  </a>
  <span v-else class="QuvelKit text-2xl font-bold text-gray-900 dark:text-white">
    <!-- Tight spacing on purpose; prevents space in text.-->
    <span class="text-blue-500">
      {{ configService.get('app_name') }}
    </span>

    Kit
    <slot></slot>
  </span>
</template>

<style scoped>
@keyframes wave {
  0%,
  100% {
    transform: translateY(3px);
  }

  50% {
    transform: translateY(-3px);
  }
}
</style>
