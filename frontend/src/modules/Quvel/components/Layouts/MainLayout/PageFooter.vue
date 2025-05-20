<script lang="ts" setup>
/**
 * Social media links
 */
const socialLinks = [
  {
    name: 'LinkedIn',
    url: 'https://www.linkedin.com/in/itsirv/',
    icon: 'eva-linkedin-outline',
  },
  {
    name: 'GitHub',
    url: 'https://github.com/ItsIrv/quvel-kit',
    icon: 'eva-github-outline',
  },
];
</script>

<template>
  <footer class="tw:mt-4 tw:py-6">
    <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:gap-3">
      <!-- Social Links -->
      <div class="tw:flex tw:items-center tw:justify-center tw:gap-6">
        <a
          v-for="link in socialLinks"
          :key="link.name"
          :href="link.url"
          target="_blank"
          rel="noopener noreferrer"
          class="social-icon-link"
          :title="link.name"
        >
          <div class="social-icon-wrapper">
            <q-icon
              :name="link.icon"
              size="24px"
              class="social-icon"
            />
          </div>
        </a>
      </div>

      <!-- Copyright -->
      <p class="tw:text-xs tw:text-gray-500 tw:dark:text-gray-400">
        © {{ new Date().getFullYear() }} QuVel Kit
      </p>
    </div>
  </footer>
</template>

<style lang="scss" scoped></style>
