<template>
  <div class="MainLayout">
    <router-view />
  </div>
</template>

<style lang="scss" scoped>
.MainLayout {
  body.body--light & {
    background: $light-page;
  }

  body.body--dark & {
    background: $dark-page;
  }
}
</style>

<script setup lang="ts">
</script>
