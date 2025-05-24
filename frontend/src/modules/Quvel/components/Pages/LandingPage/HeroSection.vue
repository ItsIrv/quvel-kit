<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from 'vue';

const rotatingFeatures = ref([
  'SSR + SPA Fallback',
  'Capacitor Native Builds',
  'Modular Architecture',
  'Strict TypeScript',
  'Pinia State Management',
  'Tailwind CSS Styling',
  'Real-time WebSockets',
]);

const currentFeatureIndex = ref(0);
let intervalId: number | undefined;

const cycleFeatures = () => {
  currentFeatureIndex.value = (currentFeatureIndex.value + 1) % rotatingFeatures.value.length;
};

onMounted(() => {
  intervalId = window.setInterval(cycleFeatures, 3000); // Rotate every 3 seconds
});

onUnmounted(() => {
  if (intervalId) {
    window.clearInterval(intervalId);
  }
});
</script>

<template>
  <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:text-center tw:mt-22 tw:mb-6">
    <h1 class="tw:!text-4xl tw:md:!text-5xl tw:lg:!text-6xl tw:!font-bold tw:mb-4">
      <span class="hero-gradient-text">{{ $t('quvel.landing.title') }}</span>
    </h1>

    <!-- Rotating Pill Display -->
    <div class="tw:pt-4 tw:flex tw:items-center tw:justify-center tw:mb-4 tw:w-full tw:max-w-4xl">
      <span class="tw:text-2xl tw:w-1/2 tw:text-right tw:pr-4">
        {{ $t('quvel.landing.subtitle') }}
      </span>

      <div class="tw:w-1/2 tw:text-left">
        <transition
          name="fade-pill"
          mode="out-in"
        >
          <span
            :key="currentFeatureIndex"
            class="rotating-pill"
          >
            <span class="hero-gradient-text tw:text-2xl">
              {{ rotatingFeatures[currentFeatureIndex] }}
            </span>
          </span>
        </transition>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../../../css/tailwind.scss';

.rotating-pill {
  @apply tw:text-3xl tw:md:text-4xl tw:lg:text-5xl tw:font-bold tw:px-6 tw:py-3 tw:rounded-full;
  @apply tw:bg-gray-100 tw:dark:bg-gray-800 tw:border tw:border-gray-300 tw:dark:border-gray-700;
}

/* Fade Transition for the Pill */
.fade-pill-enter-active,
.fade-pill-leave-active {
  transition: opacity 0.5s ease;
}

.fade-pill-enter-from,
.fade-pill-leave-to {
  opacity: 0;
}
</style>
