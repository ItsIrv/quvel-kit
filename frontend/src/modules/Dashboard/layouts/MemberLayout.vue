<template>
  <q-layout view="hHh lpR lFf">
    <!-- Header -->
    <q-header
      class="bg-white text-dark"
      bordered
    >
      <q-toolbar class="q-px-md">
        <!-- Mobile menu toggle -->
        <q-btn
          flat
          dense
          round
          icon="eva-menu-2"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          class="q-mr-sm desktop-hide"
        />

        <!-- Logo/Brand -->
        <q-toolbar-title class="flex items-center">
          <q-icon
            name="eva-activity"
            size="28px"
            class="q-mr-sm text-primary"
          />
          <span class="text-weight-medium">{{ $t('dashboard.title') }}</span>
        </q-toolbar-title>

        <q-space />

        <!-- User Menu -->
        <UserDropdownMenu />
      </q-toolbar>
    </q-header>

    <!-- Sidebar -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="260"
      :breakpoint="768"
      bordered
      class="bg-grey-1"
    >
      <q-scroll-area class="fit">
        <!-- Sidebar Header -->
        <div class="q-pa-md">
          <div class="text-subtitle1 text-weight-medium text-grey-8">
            {{ $t('dashboard.navigation') }}
          </div>
        </div>

        <!-- Navigation Items -->
        <q-list padding>
          <template
            v-for="item in navigationItems"
            :key="item.name"
          >
            <!-- Section Header -->
            <q-item-label
              v-if="item.header"
              header
              class="text-grey-7 text-uppercase text-weight-bold q-mt-md"
              style="font-size: 0.75rem; letter-spacing: 0.05em"
            >
              {{ item.label }}
            </q-item-label>

            <!-- Navigation Link -->
            <q-item
              v-else
              :to="item.to"
              exact
              active-class="bg-primary text-white"
              class="q-mb-xs q-mx-sm rounded-borders"
              clickable
              v-ripple
            >
              <q-item-section avatar>
                <q-icon :name="item.icon" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ item.label }}</q-item-label>
              </q-item-section>
              <q-item-section
                v-if="item.badge"
                side
              >
                <q-badge
                  :color="item.badge.color"
                  :label="item.badge.label"
                />
              </q-item-section>
            </q-item>
          </template>
        </q-list>

        <!-- Sidebar Footer -->
        <div class="absolute-bottom q-pa-md">
          <q-btn
            flat
            dense
            no-caps
            color="grey-7"
            icon="eva-question-mark-circle-outline"
            label="Help & Support"
            class="full-width"
          />
        </div>
      </q-scroll-area>
    </q-drawer>

    <!-- Page Container -->
    <q-page-container>
      <!-- Breadcrumbs -->
      <div
        class="q-pa-md q-pb-none"
        v-if="showBreadcrumbs"
      >
        <q-breadcrumbs class="text-grey-7">
          <q-breadcrumbs-el
            v-for="(crumb, index) in breadcrumbs"
            :key="index"
            :label="crumb.label"
            :to="crumb.to"
            :icon="crumb.icon"
          />
        </q-breadcrumbs>
      </div>

      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import UserDropdownMenu from 'src/modules/Auth/components/UserDropdownMenu.vue';

const { t } = useI18n();
const route = useRoute();

const leftDrawerOpen = ref(false);

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value;
};

// Navigation items with sections
const navigationItems = computed(() => [
  {
    name: 'dashboard',
    label: t('dashboard.nav.overview'),
    icon: 'eva-home',
    to: { name: 'dashboard' },
  },
  {
    header: true,
    label: t('dashboard.nav.sections.workspace'),
  },
  {
    name: 'projects',
    label: t('dashboard.nav.projects'),
    icon: 'eva-briefcase',
    to: { name: 'dashboard-projects' },
    badge: { color: 'primary', label: '3' },
  },
  {
    name: 'tasks',
    label: t('dashboard.nav.tasks'),
    icon: 'eva-checkmark-square-2',
    to: { name: 'dashboard-tasks' },
  },
  {
    name: 'calendar',
    label: t('dashboard.nav.calendar'),
    icon: 'eva-calendar',
    to: { name: 'dashboard-calendar' },
  },
  {
    header: true,
    label: t('dashboard.nav.sections.analytics'),
  },
  {
    name: 'reports',
    label: t('dashboard.nav.reports'),
    icon: 'eva-bar-chart-2',
    to: { name: 'dashboard-reports' },
  },
  {
    name: 'analytics',
    label: t('dashboard.nav.analytics'),
    icon: 'eva-trending-up',
    to: { name: 'dashboard-analytics' },
  },
  {
    header: true,
    label: t('dashboard.nav.sections.account'),
  },
  {
    name: 'settings',
    label: t('dashboard.nav.settings'),
    icon: 'eva-settings-2',
    to: { name: 'settings' },
  },
]);

// Breadcrumbs logic
const showBreadcrumbs = computed(() => route.meta.breadcrumbs !== false);
const breadcrumbs = computed(() => {
  const crumbs: { label: string; to?: object; icon?: string }[] = [
    { label: t('dashboard.breadcrumbs.home'), to: { name: 'dashboard' }, icon: 'eva-home' },
  ];

  if (route.meta.breadcrumbs && Array.isArray(route.meta.breadcrumbs)) {
    crumbs.push(...route.meta.breadcrumbs);
  } else if (route.meta.title) {
    crumbs.push({ label: t(route.meta.title as string) });
  }

  return crumbs;
});
</script>

<style lang="scss" scoped>
.q-item--active {
  .q-icon {
    color: white !important;
  }
}
</style>
