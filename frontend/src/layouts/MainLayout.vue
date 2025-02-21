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
    background: $light-page;
  }

  body.body--dark & {
    background: $dark-page;
  }
}
</style>

<script setup lang="ts">
</script>
