<template>
  <q-layout
    class="MainLayout"
    view="lHh Lpr lFf"
  >
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<style lang="scss" scoped>
.MainLayout {
  body.body--light & {
    background: linear-gradient(180deg, $light-page, #c5c9cf);
  }

  body.body--dark & {
    // background: $dark-page;
  }
}
</style>

<script setup lang="ts">
</script>
