<script lang="ts" setup>
import { computed, ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import ClientOnly from 'src/modules/Core/components/Misc/ClientOnly.vue';

/**
 * Props
 */
interface Props {
  /**
   * Whether to use the minimal version of the component
   */
  minimal?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  minimal: false,
});

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
  { value: 'en-US', label: 'English', flag: '🇺🇸' },
  { value: 'es-MX', label: 'Español', flag: '🇲🇽' },
];

/**
 * Computed property that returns the current locale option.
 *
 * @returns The current locale option.
 */
const currentLocale = computed(() => {
  return localeOptions.find(option => option.value === localeRef.value) || localeOptions[0];
});
</script>

<template>
  <ClientOnly>
    <!-- Standard version with q-select -->
    <q-select
      v-if="!minimal"
      v-model="localeRef"
      :options="localeOptions"
      :label="$t('common.language')"
      class="LanguageSwitcher"
      borderless
      emit-value
      map-options
    />

    <!-- Minimal version with flags -->
    <div
      v-else
      class="LanguageSwitcherMinimal"
    >
      <q-btn
        flat
        dense
        round
        class="tw:flex tw:items-center tw:justify-center tw:rounded-full tw:w-8 tw:h-8 tw:bg-gray-100 tw:dark:bg-gray-800 tw:border tw:border-gray-200 tw:dark:border-gray-700 tw:text-sm tw:focus:outline-none"
        :title="currentLocale.label"
      >
        <span>{{ currentLocale.flag }}</span>

        <q-menu
          anchor="bottom right"
          self="top right"
          class="LanguageSwitcherMenu"
        >
          <q-list style="min-width: 120px">
            <q-item
              v-for="option in localeOptions"
              :key="option.value"
              clickable
              v-close-popup
              @click="localeRef = option.value"
              :active="option.value === localeRef"
              active-class="tw:bg-gray-100 tw:dark:bg-gray-700"
            >
              <q-item-section avatar>
                <span>{{ option.flag }}</span>
              </q-item-section>
              <q-item-section>{{ option.label }}</q-item-section>
            </q-item>
          </q-list>
        </q-menu>
      </q-btn>
    </div>
  </ClientOnly>
</template>

<style lang="scss" scoped>
.LanguageSwitcher {
  width: 80px;
}

.LanguageSwitcherMinimal {
  display: inline-block;
}

.LanguageSwitcherMenu {
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}
</style>
