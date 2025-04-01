<template>
  <transition
    class=""
    enter-active-class="animated backInDown"
    leave-active-class="animated backOutUp"
  >
    <slot></slot>
  </transition>
</template>
