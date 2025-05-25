<script lang="ts" setup>
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import typescript from 'highlight.js/lib/languages/typescript';
import 'highlight.js/styles/devibeans.min.css';
import { CODE_EXAMPLES } from 'src/modules/Quvel/constants/code-examples';

// Register languages once
hljs.registerLanguage('php', php);
hljs.registerLanguage('typescript', typescript);

/**
 * State
 */
const activeTabIndex = ref(0);
const codeBlock = ref<HTMLElement | null>(null);

/**
 * Computed
 */
const activeExample = computed(() => CODE_EXAMPLES[activeTabIndex.value]);

/**
 * Methods
 */
const setActiveTab = (index: number) => {
  activeTabIndex.value = index;
};

const highlightCodeBlock = async () => {
  await nextTick();
  if (codeBlock.value) {
    codeBlock.value.removeAttribute('data-highlighted');
    hljs.highlightElement(codeBlock.value);
  }
};

/**
 * Lifecycle
 */
onMounted(async () => {
  await highlightCodeBlock();
});

// Re-highlight when tab changes
watch(
  () => activeTabIndex.value,
  async () => {
    await highlightCodeBlock();
  }
);
</script>

<template>
  <div
    class="tw:max-w-4xl tw:mx-auto tw:rounded-lg tw:overflow-hidden tw:shadow-2xl tw:border tw:border-gray-200 tw:dark:border-gray-700"
  >
    <!-- IDE Header -->
    <div
      class="tw:bg-gray-100 tw:dark:bg-gray-800 tw:px-4 tw:py-2 tw:flex tw:items-center tw:border-b tw:border-gray-200 tw:dark:border-gray-700"
    >
      <!-- Window Controls -->
      <div class="tw:flex tw:space-x-2 tw:mr-4 tw:flex-shrink-0">
        <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-red-500"></div>
        <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-yellow-500"></div>
        <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-green-500"></div>
      </div>

      <!-- Tabs Container with Horizontal Scroll -->
      <div class="tw:flex-1 tw:overflow-x-auto">
        <div class="tw:flex tw:space-x-1 tw:min-w-max">
          <button
            v-for="(example, index) in CODE_EXAMPLES"
            :key="example.key"
            class="tw:px-3 tw:py-1 tw:!text-sm tw:rounded-t-md tw:transition-colors tw:whitespace-nowrap tw:flex-shrink-0"
            :class="{
              'tw:bg-white tw:dark:bg-gray-900 tw:!text-blue-600 tw:dark:!text-blue-400': activeTabIndex === index,
              'tw:!text-gray-600 tw:dark:!text-gray-400 hover:tw:bg-gray-200 hover:tw:dark:bg-gray-700': activeTabIndex !== index
            }"
            @click="setActiveTab(index)"
          >
            {{ $t(example.title) }}
          </button>
        </div>
      </div>
    </div>

    <!-- Code Content -->
    <div class="tw:bg-white tw:dark:bg-gray-900 tw:px-4 tw:overflow-x-auto tw:max-h-[500px]">
      <pre class="tw:!text-sm tw:font-mono tw:rounded-md tw:!bg-transparent tw:!m-0">
        <code
          ref="codeBlock"
          :class="[
            `language-${activeExample?.language}`,
            'tw:!text-gray-800 tw:dark:!text-gray-200'
          ]"
        >{{ activeExample?.code }}</code>
      </pre>
    </div>
  </div>
</template>
