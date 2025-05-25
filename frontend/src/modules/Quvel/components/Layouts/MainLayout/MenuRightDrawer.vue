<script lang="ts" setup>
/**
 * MenuRightDrawer.vue is the right side drawer that opens
 * up from the hamburger button on the main nav.
 */
import { computed } from 'vue';
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Computed
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});
</script>

<template>
  <q-drawer
    v-model="inputValue"
    class="MenuRightDrawer DialogGradient"
    side="right"
    overlay
    behavior="mobile"
    bordered
  >
    <div class="MenuRightDrawer-Content">
      <!-- Header with title -->
      <div class="MenuRightDrawer-Header">
        <h5 class="MenuRightDrawer-Header-Title">{{ $t('quvel.common.menu') }}</h5>
      </div>

      <!-- Theme & Language Controls -->
      <div class="MenuRightDrawer-Controls">
        <ThemeSwitcher />
        <LanguageSwitcher />
      </div>

      <q-separator class="tw:my-4" />

      <!-- Links Section -->
      <div class="MenuRightDrawer-Links">
        <!-- Documentation Link -->
        <a
          href="https://github.com/ItsIrv/quvel-kit/tree/main/docs"
          class="MenuRightDrawer-Link"
          @click="inputValue = false"
        >
          <q-icon
            name="eva-book-outline"
            size="sm"
            class="MenuRightDrawer-LinkIcon"
          />
          <div class="MenuRightDrawer-LinkContent">
            <h6 class="MenuRightDrawer-LinkContent-Title">{{ $t('quvel.lander.docs') }}</h6>
            <p class="MenuRightDrawer-LinkContent-Description">{{ $t('quvel.lander.docsDescription') }}</p>
          </div>
        </a>

        <!-- GitHub Link -->
        <a
          href="https://github.com/ItsIrv/quvel-kit"
          class="MenuRightDrawer-Link"
          @click="inputValue = false"
        >
          <q-icon
            name="eva-github-outline"
            size="sm"
            class="MenuRightDrawer-LinkIcon"
          />
          <div class="MenuRightDrawer-LinkContent">
            <h6 class="MenuRightDrawer-LinkContent-Title">{{ $t('quvel.lander.github') }}</h6>
            <p class="MenuRightDrawer-LinkContent-Description">{{ $t('quvel.lander.githubDescription') }}</p>
          </div>
        </a>
      </div>
    </div>
  </q-drawer>
</template>

<style lang="scss">
/* Styles moved to drawer-components.scss */
</style>
