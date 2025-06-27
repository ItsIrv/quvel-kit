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
import { useContainer } from 'src/modules/Core/composables/useContainer';
import UserDropdownMenu from 'src/modules/Auth/components/UserDropdownMenu.vue';
import { DashboardRoutes } from '../router/constants';

const { i18n } = useContainer();
const route = useRoute();

const leftDrawerOpen = ref(false);

const toggleLeftDrawer = () => {
  leftDrawerOpen.value = !leftDrawerOpen.value;
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

<style lang="scss" scoped>
.q-item--active {
  .q-icon {
    color: white !important;
  }
}
</style>
