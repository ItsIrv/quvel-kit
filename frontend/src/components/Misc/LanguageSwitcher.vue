<template>
  <q-select
    v-model="localeRef"
    :options="localeOptions"
    :label="$t('common.language')"
    class="LanguageSwitcher"
    dense
    borderless
    emit-value
    map-options
    options-dense
  />
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();
const i18n = container.i18n;

const localeRef = computed({
  get: () => i18n.instance.global.locale.value,
  set: (locale) => {
    container.i18n.changeLocale(locale);
  }
});

const localeOptions = [
  { value: 'en-US', label: 'English' },
  { value: 'es-MX', label: 'Español' }
];
</script>

<style lang="scss" scoped>
.LanguageSwitcher {
  width: 80px;
}
</style>
