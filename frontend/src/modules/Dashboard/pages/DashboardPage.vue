<template>
  <q-page class="q-pa-md">
    <!-- Page Header -->
    <div class="row q-mb-lg">
      <div class="col-12 col-md-6">
        <h1 class="text-h4 q-my-none">{{ $t('dashboard.welcome', { name: userName }) }}</h1>
        <p class="text-subtitle1 text-grey-7 q-mb-none">
          {{ $t('dashboard.subtitle') }}
        </p>
      </div>
      <div class="col-12 col-md-6 text-right gt-sm">
        <q-btn
          color="primary"
          icon="eva-plus"
          :label="$t('dashboard.actions.create')"
          unelevated
          class="q-px-lg"
        />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6 col-md-3" v-for="stat in stats" :key="stat.title">
        <q-card flat bordered>
          <q-card-section>
            <div class="row items-center no-wrap">
              <div class="col">
                <div class="text-subtitle2 text-grey-7">{{ stat.title }}</div>
                <div class="text-h5 q-mt-sm q-mb-xs">{{ stat.value }}</div>
                <div class="text-caption" :class="stat.trendColor">
                  <q-icon :name="stat.trendIcon" size="16px" />
                  {{ stat.trend }}
                </div>
              </div>
              <div class="col-auto">
                <q-icon :name="stat.icon" size="48px" :color="stat.color" />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md-8">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">{{ $t('dashboard.quickActions.title') }}</div>
            <div class="row q-col-gutter-sm">
              <div
                class="col-6 col-sm-4 col-md-3"
                v-for="action in quickActions"
                :key="action.label"
              >
                <q-btn
                  :icon="action.icon"
                  :label="action.label"
                  :color="action.color"
                  unelevated
                  stack
                  class="full-width q-py-md"
                  @click="handleQuickAction(action.action)"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <div class="col-12 col-md-4">
        <q-card flat bordered class="full-height">
          <q-card-section>
            <div class="text-h6 q-mb-md">{{ $t('dashboard.activity.title') }}</div>
            <q-list>
              <q-item v-for="activity in recentActivity" :key="activity.id" class="q-px-none">
                <q-item-section avatar>
                  <q-avatar :color="activity.color" text-color="white" size="32px">
                    <q-icon :name="activity.icon" size="18px" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ activity.title }}</q-item-label>
                  <q-item-label caption>{{ activity.time }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Content Area -->
    <div class="row q-col-gutter-md">
      <div class="col-12">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 q-mb-md">{{ $t('dashboard.getStarted.title') }}</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4" v-for="guide in guides" :key="guide.title">
                <q-card flat bordered>
                  <q-card-section>
                    <q-icon :name="guide.icon" size="32px" :color="guide.color" class="q-mb-sm" />
                    <div class="text-subtitle1 q-mb-xs">{{ guide.title }}</div>
                    <div class="text-body2 text-grey-7">{{ guide.description }}</div>
                  </q-card-section>
                  <q-card-actions>
                    <q-btn flat color="primary" :label="$t('dashboard.actions.learnMore')" />
                  </q-card-actions>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useQuasar } from 'quasar';

const { t } = useI18n();
const $q = useQuasar();
const sessionStore = useSessionStore();

const userName = computed(() => sessionStore.getUser?.name || 'User');

// Dashboard stats
const stats = computed(() => [
  {
    title: t('dashboard.stats.projects'),
    value: '12',
    trend: '+15%',
    trendIcon: 'eva-trending-up',
    trendColor: 'text-positive',
    icon: 'eva-briefcase',
    color: 'primary',
  },
  {
    title: t('dashboard.stats.tasks'),
    value: '48',
    trend: '+8%',
    trendIcon: 'eva-trending-up',
    trendColor: 'text-positive',
    icon: 'eva-checkmark-square-2',
    color: 'secondary',
  },
  {
    title: t('dashboard.stats.team'),
    value: '6',
    trend: '0%',
    trendIcon: 'eva-minus',
    trendColor: 'text-grey-6',
    icon: 'eva-people',
    color: 'accent',
  },
  {
    title: t('dashboard.stats.revenue'),
    value: '$24.5k',
    trend: '-3%',
    trendIcon: 'eva-trending-down',
    trendColor: 'text-negative',
    icon: 'eva-credit-card',
    color: 'positive',
  },
]);

// Quick action buttons
const quickActions = computed(() => [
  {
    label: t('dashboard.quickActions.newProject'),
    icon: 'eva-plus-square',
    color: 'primary',
    action: 'new-project',
  },
  {
    label: t('dashboard.quickActions.invite'),
    icon: 'eva-person-add',
    color: 'secondary',
    action: 'invite-member',
  },
  {
    label: t('dashboard.quickActions.report'),
    icon: 'eva-bar-chart',
    color: 'accent',
    action: 'generate-report',
  },
  {
    label: t('dashboard.quickActions.upload'),
    icon: 'eva-cloud-upload',
    color: 'info',
    action: 'upload-files',
  },
]);

// Recent activity
const recentActivity = computed(() => [
  {
    id: 1,
    title: t('dashboard.activity.projectCreated'),
    time: '2 hours ago',
    icon: 'eva-folder-add',
    color: 'primary',
  },
  {
    id: 2,
    title: t('dashboard.activity.taskCompleted'),
    time: '4 hours ago',
    icon: 'eva-checkmark-circle-2',
    color: 'positive',
  },
  {
    id: 3,
    title: t('dashboard.activity.memberJoined'),
    time: 'Yesterday',
    icon: 'eva-person-add',
    color: 'secondary',
  },
]);

// Getting started guides
const guides = computed(() => [
  {
    title: t('dashboard.guides.setup.title'),
    description: t('dashboard.guides.setup.description'),
    icon: 'eva-settings',
    color: 'primary',
  },
  {
    title: t('dashboard.guides.tutorial.title'),
    description: t('dashboard.guides.tutorial.description'),
    icon: 'eva-book-open',
    color: 'secondary',
  },
  {
    title: t('dashboard.guides.api.title'),
    description: t('dashboard.guides.api.description'),
    icon: 'eva-code',
    color: 'accent',
  },
]);

const handleQuickAction = (action: string) => {
  // Handle quick actions
  $q.notify({
    message: `Action: ${action}`,
    position: 'top',
    timeout: 2000,
  });
};
</script>