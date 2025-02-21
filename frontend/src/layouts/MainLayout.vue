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
    background: linear-gradient(180deg, #bfcce4, #c5c9cf);
  }

  body.body--dark & {
    background: linear-gradient(180deg, #0c4160, #0f224e);
  }
}
</style>

<script setup lang="ts">
</script>
