<script lang="ts" setup>
import IDEWindow from '../components/Pages/LandingPage/IDEWindow.vue';
import HeroSection from '../components/Pages/LandingPage/HeroSection.vue';
</script>

<template>
  <div class="LandingPage tw:flex-grow tw:relative tw:overflow-hidden">
    <!-- Hero Content -->
    <div class="tw:container tw:mx-auto tw:px-4 tw:relative tw:z-10">
      <!-- Hero Section -->
      <HeroSection />

      <!-- IDE Window -->
      <IDEWindow />
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../css/tailwind.scss';

/* Override highlight.js styles to respect Tailwind dark/light theme */
.hljs {
  /* Reset background to be transparent */
  background: transparent !important;
}

.social-icon-link {
  @apply tw:relative tw:block;
}

.social-icon-wrapper {
  @apply tw:flex tw:items-center tw:justify-center tw:w-10 tw:h-10 tw:rounded-full tw:bg-gray-100 tw:dark:bg-gray-800 tw:text-gray-600 tw:dark:text-gray-400 tw:transition-all tw:duration-300 tw:ease-in-out;

  &:hover {
    @apply tw:bg-blue-500/10 tw:text-blue-500 tw:scale-110 tw:shadow-md;
    transform-origin: center;
  }

  &:active {
    @apply tw:scale-95 tw:shadow-inner;
  }
}

.hero-gradient-text {
  @apply tw:text-transparent tw:bg-clip-text;
  background-image: linear-gradient(135deg, #3b82f6, #f3552c);
}

/* Landing Page Background */
.LandingBackground {
  position: relative;
  overflow: hidden;
  z-index: 0;

  /* Base background color */
  @apply tw:bg-gradient-to-b tw:from-gray-50 tw:to-white tw:dark:from-gray-900 tw:dark:to-gray-800;

  /* Animated gradient blobs */
  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 70%;
    height: 70%;
    filter: blur(90px);
    border-radius: 50%;
    opacity: 0.25;
    z-index: -1;
    animation-duration: 12s;
    animation-iteration-count: infinite;
    animation-timing-function: ease-in-out;
  }

  /* First blob - Blue */
  &::before {
    top: -10%;
    left: -10%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa);
    animation-name: blob-move-1;

    .dark & {
      opacity: 0.2;
      background: linear-gradient(90deg, #3b82f6, #818cf8);
    }
  }

  /* Second blob - Orange/Red */
  &::after {
    bottom: -10%;
    right: -10%;
    background: linear-gradient(90deg, #f97316, #f43f5e);
    animation-name: blob-move-2;
    animation-delay: 1s;

    .dark & {
      opacity: 0.18;
      background: linear-gradient(90deg, #f97316, #f43f5e);
    }
  }

  /* Add a third blob - Purple */
  .blob-purple {
    content: '';
    position: absolute;
    top: 40%;
    left: 40%;
    width: 50%;
    height: 50%;
    background: linear-gradient(90deg, #8b5cf6, #d946ef);
    filter: blur(80px);
    border-radius: 50%;
    opacity: 0.25;
    z-index: -1;
    animation: blob-move-3 15s infinite ease-in-out;
    animation-delay: 2s;

    .dark & {
      opacity: 0.2;
      background: linear-gradient(90deg, #8b5cf6, #d946ef);
    }
  }

  /* Grid overlay */
  .grid-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image:
      linear-gradient(rgba(59, 130, 246, 0.15) 1px, transparent 1px),
      linear-gradient(90deg, rgba(59, 130, 246, 0.15) 1px, transparent 1px);
    background-size: 40px 40px;
    z-index: -1;
    opacity: 0.7;

    .dark & {
      background-image:
        linear-gradient(rgba(99, 102, 241, 0.2) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.2) 1px, transparent 1px);
      opacity: 0.5;
    }
  }
}

/* Animations for the landing page background */
@keyframes blob-move-1 {

  0%,
  100% {
    transform: translate(0, 0) scale(1);
  }

  25% {
    transform: translate(5%, 10%) scale(1.1);
  }

  50% {
    transform: translate(10%, 5%) scale(0.9);
  }

  75% {
    transform: translate(5%, 15%) scale(1.05);
  }
}

@keyframes blob-move-2 {

  0%,
  100% {
    transform: translate(0, 0) scale(1);
  }

  25% {
    transform: translate(-5%, -10%) scale(1.1);
  }

  50% {
    transform: translate(-10%, -5%) scale(0.9);
  }

  75% {
    transform: translate(-5%, -15%) scale(1.05);
  }
}

.quvel-link {
  @apply tw:relative tw:inline-block tw:font-medium tw:text-gray-700 tw:dark:text-gray-200;
  @apply tw:transition-colors tw:duration-300 tw:ease-out tw:text-xl;
  text-decoration: none;

  &::before {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    width: 0;
    height: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #f97316 100%);
    transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    transform: translateX(-50%);
    border-radius: 1px;
  }

  &:hover {
    color: #3b82f6;

    &::before {
      width: 100%;
      height: 2px;
    }
  }

  // Dark mode adjustments
  .dark & {
    &::before {
      background: linear-gradient(135deg, #3b82f6 0%, #f97316 100%);
    }
  }

  // Active state
  &:active {
    transform: translateY(1px);

    &::before {
      height: 2px;
    }
  }

  // Focus state
  &:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
    border-radius: 6px;
  }
}
</style>
