<template>
  <q-layout view="hHh lpR lFf">
    <!-- Header -->
    <q-header bordered>
      <q-toolbar class="q-px-md">
        <!-- Mobile hamburger menu -->
        <q-btn
          flat
          dense
          round
          icon="eva-menu-2"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          class="q-mr-sm lt-lg"
        />

        <!-- Logo and App Name -->
        <q-toolbar-title class="row items-center no-wrap">
          <q-icon
            name="eva-activity"
            size="28px"
            class="q-mr-sm"
          />
          <span class="text-weight-medium">Dashboard</span>
        </q-toolbar-title>

        <q-space />

        <!-- Desktop Theme & Language Switchers -->
        <div class="row q-gutter-sm gt-md">
          <ThemeSwitcher />
          <LanguageSwitcher />
        </div>

        <!-- User Avatar and Name -->
        <div class="row items-center no-wrap q-ml-md">
          <img
            :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'"
            alt="User Avatar"
            class="tw:w-8 tw:h-8 tw:rounded-full tw:border tw:border-stone-300 tw:dark:border-gray-600 tw:shadow-sm q-mr-sm"
          />
          <span class="text-weight-medium gt-sm">{{ sessionStore.user?.name || 'User' }}</span>
        </div>

        <!-- User Dropdown -->
        <UserDropdownMenu />
      </q-toolbar>
    </q-header>

    <!-- Collapsible Sidebar -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      :width="260"
      :mini="miniDrawer && $q.screen.gt.md"
      :breakpoint="1024"
      bordered
    >
      <q-scroll-area class="fit">
        <!-- Sidebar Header with collapse toggle for desktop -->
        <div class="q-pa-md row items-center justify-between no-wrap">
          <div
            class="text-subtitle1 text-weight-medium"
            v-if="!miniDrawer || $q.screen.lt.lg"
          >
            Navigation
          </div>
          <q-btn
            flat
            dense
            round
            icon="eva-chevron-left"
            size="sm"
            class="gt-md"
            @click="toggleMiniDrawer"
            :class="{ 'rotate-180': miniDrawer }"
          />
        </div>

        <!-- Navigation Items -->
        <q-list padding>
          <template
            v-for="item in navigationItems"
            :key="item.name"
          >
            <!-- Navigation Link -->
            <q-item
              :to="item.to"
              exact
              class="rounded-borders"
              clickable
              v-ripple
            >
              <q-item-section avatar>
                <q-icon :name="item.icon" />
              </q-item-section>
              <q-item-section v-if="!miniDrawer || $q.screen.lt.lg">
                <q-item-label>{{ item.label }}</q-item-label>
              </q-item-section>
              <q-item-section
                v-if="item.badge && (!miniDrawer || $q.screen.lt.lg)"
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
        <div
          class="absolute-bottom q-pa-md"
          v-if="!miniDrawer || $q.screen.lt.lg"
        >
          <q-btn
            flat
            dense
            no-caps
            icon="eva-question-mark-circle-outline"
            label="Help & Support"
            class="full-width text-gray-600 dark:text-gray-400"
          />
        </div>
      </q-scroll-area>
    </q-drawer>

    <!-- Page Container -->
    <q-page-container>
      <div class="dashboard-container">
        <!-- Breadcrumbs -->
        <div
          class="q-pb-none"
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
      </div>
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useQuasar } from 'quasar';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import UserDropdownMenu from 'src/modules/Auth/components/UserDropdownMenu.vue';
import ThemeSwitcher from 'src/modules/Core/components/Misc/ThemeSwitcher.vue';
import LanguageSwitcher from 'src/modules/Core/components/Misc/LanguageSwitcher.vue';
import { DashboardRoutes } from '../router/constants';

const { i18n } = useContainer();
const route = useRoute();
const $q = useQuasar();
const sessionStore = useSessionStore();

const leftDrawerOpen = ref(false);
const miniDrawer = ref(false);

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value;
};

const toggleMiniDrawer = () => {
  miniDrawer.value = !miniDrawer.value;
};

// Navigation items with sections
interface NavigationItem {
  name?: string;
  label: string;
  icon?: string;
  to?: { name: string };
  header?: boolean;
  badge?: { color: string; label: string };
}

const navigationItems = computed((): NavigationItem[] => [
  {
    name: 'dashboard',
    label: i18n.t('dashboard.nav.overview'),
    icon: 'eva-home',
    to: { name: DashboardRoutes.DASHBOARD },
  },
]);

// Breadcrumbs logic
const showBreadcrumbs = computed(() => route.meta.breadcrumbs !== false);
const breadcrumbs = computed(() => {
  const crumbs: { label: string; to?: object; icon?: string }[] = [
    { label: i18n.t('dashboard.breadcrumbs.home'), to: { name: DashboardRoutes.DASHBOARD }, icon: 'eva-home' },
  ];

  if (route.meta.breadcrumbs && Array.isArray(route.meta.breadcrumbs)) {
    crumbs.push(...route.meta.breadcrumbs);
  } else if (route.meta.title) {
    crumbs.push({ label: i18n.t(route.meta.title as string) });
  }

  return crumbs;
});
</script>
