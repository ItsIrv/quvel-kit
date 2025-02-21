<template>
  <q-select
    v-model="locale"
    :options="localeOptions"
    :label="$t('common.language')"
    dropdown-icon="eva-arrow-down-outline"
    class="LanguageSwitcher"
    dense
    borderless
    emit-value
    map-options
    options-dense
  />
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n';

const { locale } = useI18n({ useScope: 'global' })

const localeOptions = [
  { value: 'en-US', label: 'English' },
  { value: 'es-MX', label: 'Español' }
];
</script>

<style lang="scss" scoped>
.LanguageSwitcher {
  width: 90px;
}
</style>
