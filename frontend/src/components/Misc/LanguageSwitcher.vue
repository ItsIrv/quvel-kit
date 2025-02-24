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
import { applyLocale } from 'src/utils/i18nUtil';

const container = useContainer();
const i18n = container.i18n;

const localeRef = computed({
  get: () => i18n.instance.global.locale.value,
  set: (val) => {
    applyLocale(i18n.instance, val);
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
