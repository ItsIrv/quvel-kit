<script lang="ts" setup>
import { ref, onMounted, onUnmounted } from 'vue';

/**
 * Props
 */
const props = defineProps({
  // Content props
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  // Styling props
  height: {
    type: String,
    default: 'tw:py-12',
  },
  baseOpacity: {
    type: Number,
    default: 0.4,
  },
  illuminationRadius: {
    type: Number,
    default: 150,
  },
  maxIllumination: {
    type: Number,
    default: 0.9,
  },
  starColor: {
    type: String,
    default: '#3b82f6',
  },
  starColorDark: {
    type: String,
    default: '#f3552c',
  },
  backgroundGradient: {
    type: String,
    default: 'transparent',
  },
});

/**
 * Mouse tracking
 */
const container = ref<HTMLElement | null>(null);
const mouseX = ref(0);
const mouseY = ref(0);
const isHovering = ref(false);

const updateMousePosition = (event: MouseEvent) => {
  if (!container.value) return;

  const rect = container.value.getBoundingClientRect();
  mouseX.value = event.clientX - rect.left;
  mouseY.value = event.clientY - rect.top;
};

const handleMouseEnter = () => {
  isHovering.value = true;
};

const handleMouseLeave = () => {
  isHovering.value = false;
};

onMounted(() => {
  if (container.value) {
    container.value.addEventListener('mousemove', updateMousePosition);
    container.value.addEventListener('mouseenter', handleMouseEnter);
    container.value.addEventListener('mouseleave', handleMouseLeave);
  }
});

onUnmounted(() => {
  if (container.value) {
    container.value.removeEventListener('mousemove', updateMousePosition);
    container.value.removeEventListener('mouseenter', handleMouseEnter);
    container.value.removeEventListener('mouseleave', handleMouseLeave);
  }
});
</script>

<template>
  <div
    ref="container"
    class="SubtleIlluminatedStars"
    :class="height"
    :style="{
      background: backgroundGradient,
      '--mouse-x': `${mouseX}px`,
      '--mouse-y': `${mouseY}px`,
      '--base-opacity': baseOpacity,
      '--illumination-radius': `${illuminationRadius}px`,
      '--max-illumination': maxIllumination,
      '--star-color': starColor,
      '--star-color-dark': starColorDark,
    }"
  >
    <!-- Base Stars Layer (Very Subtle) -->
    <div class="SubtleIlluminatedStars-BaseLayer"></div>

    <!-- Illuminated Stars Layer (Appears on Hover) -->
    <div
      class="SubtleIlluminatedStars-IlluminatedLayer"
      :class="{ 'SubtleIlluminatedStars-IlluminatedLayer--active': isHovering }"
    ></div>

    <!-- Content Container -->
    <div class="SubtleIlluminatedStars-Content">
      <slot>
        <!-- Default content if no slot provided -->
        <div
          v-if="title || subtitle"
          class="SubtleIlluminatedStars-DefaultContent"
        >
          <h1
            v-if="title"
            class="SubtleIlluminatedStars-Title"
          >
            {{ title }}
          </h1>
          <p
            v-if="subtitle"
            class="SubtleIlluminatedStars-Subtitle"
          >
            {{ subtitle }}
          </p>
        </div>
      </slot>
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../../../css/tailwind.scss';

.SubtleIlluminatedStars {
  @apply tw:relative tw:mb-8 tw:overflow-hidden;

  &-BaseLayer {
    @apply tw:absolute tw:inset-0 tw:z-0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: var(--base-opacity);
    transition: all 0.3s ease;

    .dark & {
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f3552c' fill-opacity='0.35'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
  }

  &-IlluminatedLayer {
    @apply tw:absolute tw:inset-0 tw:z-0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.9'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0;
    transition: opacity 0.3s ease;

    .dark & {
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23f3552c' fill-opacity='0.7'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    mask: radial-gradient(circle var(--illumination-radius) at var(--mouse-x) var(--mouse-y),
      rgba(255, 255, 255, var(--max-illumination)) 0%,
      rgba(255, 255, 255, 0.2) 50%,
      rgba(255, 255, 255, 0) 80%);
    -webkit-mask: radial-gradient(circle var(--illumination-radius) at var(--mouse-x) var(--mouse-y),
      rgba(255, 255, 255, var(--max-illumination)) 0%,
      rgba(255, 255, 255, 0.2) 50%,
      rgba(255, 255, 255, 0) 80%);

    &--active {
      opacity: 1;
    }
  }

  &-Content {
    @apply tw:container tw:mx-auto tw:px-6 tw:relative tw:z-10;
    max-width: 1200px;
    color: inherit;

    .dark & {
      color: inherit;
    }
  }

  &-DefaultContent {
    @apply tw:text-center;
  }

  &-Title {
    @apply tw:text-3xl tw:md:text-4xl tw:font-bold tw:mb-2;
    @apply tw:text-gray-900 tw:dark:text-white;
  }

  &-Subtitle {
    @apply tw:text-gray-600 tw:dark:text-gray-300 tw:text-lg;
  }
}
</style>
