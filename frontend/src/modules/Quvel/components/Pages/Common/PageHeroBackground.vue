<script lang="ts" setup>
/**
 * Props
 */
defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  height: {
    type: String,
    default: 'tw:py-1',
  },
});
</script>

<template>
  <div
    class="PageHero"
    :class="height"
  >
    <div class="PageHero-Content">
      <slot>
        <div
          v-if="title || subtitle"
          class="PageHero-DefaultContent"
        >
          <h1
            v-if="title"
            class="PageHero-Title"
          >
            {{ title }}
          </h1>
          <p
            v-if="subtitle"
            class="PageHero-Subtitle"
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

.PageHero {
  @apply tw:mb-8;

  &-Content {
    @apply tw:container tw:mx-auto tw:px-6;
    max-width: 1200px;
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
