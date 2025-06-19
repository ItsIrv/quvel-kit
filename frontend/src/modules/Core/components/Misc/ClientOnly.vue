<script lang="ts" setup>
import { useClient } from 'src/modules/Core/composables/useClient';

/**
 * ClientOnly component
 *
 * Renders its slot content only on the client-side after hydration.
 * Prevents hydration mismatches for components that rely on browser-only APIs.
 */

const { isClient } = useClient();
</script>

<template>
  <template v-if="isClient">
    <slot />
  </template>

  <template v-else>
    <slot name="fallback" />
  </template>
</template>
