<script lang="ts" setup>
import { computed } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import ClientOnly from 'src/modules/Core/components/Misc/ClientOnly.vue';

/**
 * LanguageSwitcher component switcher for the i18n locale.
 */

/**
 * Container
 */
const { i18n } = useContainer();

/**
 * Computed property that returns the current locale value and sets the locale
 * when updated.
 *
 * @returns The current locale value.
 */
const localeRef = computed({
  /**
   * Getter function for the computed property.
   *
   * @returns The current locale value.
   */
  get: () => i18n.instance.global.locale.value,

  /**
   * Setter function for the computed property.
   *
   * @param locale - The new locale value to set.
   */
  set: (locale) => {
    i18n.changeLocale(locale);
  },
});

/**
 * Array of locale options for the select component.
 */
const localeOptions = [
  { value: 'en-US', label: 'English' },
  { value: 'es-MX', label: 'Español' },
];
</script>

<template>
  <ClientOnly>
    <q-select
      v-model="localeRef"
      :options="localeOptions"
      :label="$t('common.language')"
      class="LanguageSwitcher"
      borderless
      emit-value
      map-options
    />
  </ClientOnly>
</template>

<style lang="scss" scoped>
.LanguageSwitcher {
  width: 80px;
}
</style>
