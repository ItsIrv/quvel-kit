<script lang="ts" setup>
defineProps({
  animate: {
    type: Boolean,
    required: false,
    default: false
  }
});
</script>

<template>
  <span class="QuvelKit text-2xl font-bold text-gray-900 dark:text-white">
    <!-- Tight spacing on purpose; prevents space in text.-->
    <span
      class="text-blue-500"
      :class="{ 'animate-wave': animate }"
    >Qu<span
        class="text-orange-600"
        :class="{ 'animate-wave delay-1': animate }"
      >Vel</span>
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

.animate-wave {
  display: inline-block;
  animation: wave 3s ease-in-out infinite;
}

/* Slight delay for staggered effect */
.delay-1 {
  animation-delay: 0.2s;
}
</style>
