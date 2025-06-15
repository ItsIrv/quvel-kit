<script lang="ts" setup>
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { config } = useContainer();

defineProps({
  link: {
    type: Boolean,
    required: false,
    default: false,
  },
});
</script>

<template>
  <router-link
    v-if="link"
    to="/"
  >
    <span class="QuvelKit tw:text-2xl quvel-link tw:font-bold tw:text-gray-900 tw:dark:text-white">
      <!-- Tight spacing on purpose; prevents space in text.-->
      <span class="tw:text-blue-500">
        {{ config.get('appName') }}
      </span>

      <slot></slot>
    </span>
  </router-link>
  <span
    v-else
    class="QuvelKit tw:text-2xl tw:font-bold tw:text-gray-900 tw:dark:text-white"
  >
    <!-- Tight spacing on purpose; prevents space in text.-->
    <span class="tw:text-blue-500 tw:mr-2">
      {{ config.get('appName') }}
    </span>

    <slot></slot>
  </span>
</template>
