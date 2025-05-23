<template>
  <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:text-center tw:mt-18 tw:mb-6">
    <h1 class="tw:!text-4xl tw:md:!text-5xl tw:lg:!text-6xl tw:!font-bold tw:mb-4">
      <span class="hero-gradient-text">{{ $t('quvel.landing.title') }}</span>
    </h1>

    <p class="tw:!text-xl tw:md:!text-2xl tw:!font-medium tw:mb-4 tw:text-gray-700 tw:dark:!text-gray-200 tw:max-w-4xl">
      {{ $t('quvel.landing.subtitle') }}
    </p>

    <p class="tw:!text-lg tw:text-gray-600 tw:dark:!text-gray-300 tw:max-w-2xl">
      {{ $t('quvel.landing.description') }}
    </p>
  </div>
</template>

<style lang="scss">
.hero-gradient-text {
  @reference tw:text-transparent tw:bg-clip-text;
  background-image: linear-gradient(135deg, #3b82f6, #f3552c);
}
</style>
