<template>
  <q-select
    v-model="localeRef"
    :options="localeOptions"
    :label="$t('common.language')"
    class="LanguageSwitcher"
    borderless
    emit-value
    map-options
  />
</template>

<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { i18n } = useContainer();

const localeRef = computed({
  get: () => i18n.instance.global.locale.value,
  set: (locale) => {
    i18n.changeLocale(locale);
  },
});

const localeOptions = [
  { value: 'en-US', label: 'English' },
  { value: 'es-MX', label: 'Español' },
];
</script>

<style lang="scss" scoped>
.LanguageSwitcher {
  width: 80px;
}
</style>
