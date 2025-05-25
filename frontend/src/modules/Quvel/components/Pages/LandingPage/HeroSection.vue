<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from 'vue';

const rotatingFeatures = ref([
  'quvel.landing.features.ssr',
  'quvel.landing.features.capacitor',
  'quvel.landing.features.modular',
  'quvel.landing.features.typescript',
  'quvel.landing.features.pinia',
  'quvel.landing.features.tailwind',
  'quvel.landing.features.websockets',
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
  <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:text-center tw:mt-10 tw:mb-6">
    <h1 class="tw:!text-5xl tw:lg:!text-6xl tw:!font-bold tw:mb-4">
      <span class="hero-gradient-text">{{ $t('quvel.landing.title') }}</span>
    </h1>

    <!-- Rotating Pill Display -->
    <div
      class="tw:pt-4 tw:flex tw:flex-col tw:md:flex-row tw:items-center tw:justify-center tw:mb-4 tw:w-full tw:max-w-4xl"
    >
      <span
        class="tw:text-2xl tw:w-full tw:-mt-1.5 tw:md:w-1/2 tw:text-center tw:md:text-right tw:pr-0 tw:md:pr-4 tw:py-2"
      >
        {{ $t('quvel.landing.subtitle') }}
      </span>

      <div class="tw:w-full tw:md:!w-1/2 tw:text-center tw:md:text-left">
        <transition
          enter-active-class="animated fadeIn"
          leave-active-class="animated fadeOut"
          mode="out-in"
        >
          <span
            :key="currentFeatureIndex"
            class="rotating-pill"
          >
            <span class="hero-gradient-text tw:text-2xl">
              {{ $t(rotatingFeatures[currentFeatureIndex] || '') }}
            </span>
          </span>
        </transition>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../../../css/tailwind.scss';

/* Slide Transition for the Pill */
.rotating-pill {
  display: inline-block;
  position: relative;
  overflow: hidden;
}
</style>
