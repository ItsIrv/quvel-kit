<template>
  <!--
    This transition gives a "growing" effect to the parent container.
    A great use case is dynamic form fields on a vertical form. The form doesn't
    just flash into its new size, offering a more aesthetically pleasing effect.
  -->
  <transition
    name="slow-expand"
    appear
  >
    <slot />
  </transition>
</template>
