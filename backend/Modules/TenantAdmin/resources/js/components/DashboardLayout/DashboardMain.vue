<script setup lang="ts">
// Props
interface Props {
  sidebarVisible: boolean
}

const props = defineProps<Props>()
</script>

<template>
  <div class="flex-1 flex flex-col" :class="{ 'ml-64': props.sidebarVisible }">
    <slot name="header" />
    
    <!-- Page Content -->
    <main class="flex-1 p-6 bg-gray-50">
      <slot />
    </main>
  </div>
</template>