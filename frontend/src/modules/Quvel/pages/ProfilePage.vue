<script lang="ts" setup>
import { computed } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import PageHeroBackground from '../components/Pages/Common/PageHeroBackground.vue';

/**
 * Services
 */
const sessionStore = useSessionStore();

/**
 * Computed
 */
const user = computed(() => sessionStore.getUser);
</script>

<template>
  <div class="ProfilePage">
    <!-- Hero Section with User Info -->
    <PageHeroBackground
      :title="$t('quvel.profile.title')"
      :subtitle="$t('quvel.profile.subtitle')"
    />

    <!-- Profile Content -->
    <div class="ProfileContent">
      <div class="ProfileGrid">
        <!-- Stats Overview -->
        <div class="ProfileCard ProfileCard--stats">
          <div class="ProfileStat">
            <div class="ProfileStat-Value">12</div>
            <div class="ProfileStat-Label">{{ $t('quvel.profile.projects') }}</div>
          </div>

          <div class="ProfileStat">
            <div class="ProfileStat-Value">3</div>
            <div class="ProfileStat-Label">{{ $t('quvel.profile.teams') }}</div>
          </div>

          <div class="ProfileStat">
            <div class="ProfileStat-Value">86</div>
            <div class="ProfileStat-Label">{{ $t('quvel.profile.contributions') }}</div>
          </div>
        </div>

        <!-- Personal Information -->
        <div class="ProfileCard">
          <div class="ProfileCard-Header">
            <h2>{{ $t('quvel.profile.personalInfo') }}</h2>
          </div>

          <div class="ProfileCard-Content">
            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.name') }}</div>
              <div class="ProfileDetail-Value">{{ user?.name }}</div>
            </div>

            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.email') }}</div>
              <div class="ProfileDetail-Value">{{ user?.email }}</div>
            </div>

            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.role') }}</div>
              <div class="ProfileDetail-Value">{{ $t('quvel.profile.defaultRole') }}</div>
            </div>
          </div>
        </div>

        <!-- Account Information -->
        <div class="ProfileCard">
          <div class="ProfileCard-Header">
            <h2>{{ $t('quvel.profile.accountInfo') }}</h2>
          </div>

          <div class="ProfileCard-Content">
            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.status') }}</div>
              <div class="ProfileDetail-Value ProfileDetail-Value--success">{{ $t('quvel.profile.active') }}</div>
            </div>

            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.lastLogin') }}</div>
              <div class="ProfileDetail-Value">{{ $t('quvel.profile.today') }}</div>
            </div>

            <div class="ProfileDetail">
              <div class="ProfileDetail-Label">{{ $t('quvel.profile.memberSince') }}</div>
              <div class="ProfileDetail-Value">{{ $t('quvel.profile.joinDate') }}</div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="ProfileCard ProfileCard--wide">
          <div class="ProfileCard-Header">
            <h2>{{ $t('quvel.profile.recentActivity') }}</h2>
          </div>

          <div class="ProfileCard-Content ProfileCard-Content--empty">
            <q-icon
              name="eva-activity-outline"
              size="48px"
            />
            <p>{{ $t('quvel.profile.noRecentActivity') }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../css/tailwind.scss';

.ProfilePage {
  @apply tw:min-h-screen tw:pb-16;
}

/* Profile Content */
.ProfileContent {
  @apply tw:container tw:mx-auto tw:px-6;
  max-width: 1200px;
}

.ProfileGrid {
  @apply tw:grid tw:grid-cols-1 tw:md:grid-cols-2 tw:gap-6;
}

/* Profile Cards */
.ProfileCard {
  @apply tw:bg-white/80 tw:dark:bg-gray-800/80 tw:rounded-xl tw:overflow-hidden tw:shadow-sm tw:backdrop-blur-sm;
  border: 1px solid rgba(255, 255, 255, 0.1);

  &--wide {
    @apply tw:col-span-1 tw:md:col-span-2;
  }

  &--stats {
    @apply tw:col-span-1 tw:md:col-span-2 tw:py-6 tw:flex tw:justify-around tw:items-center tw:gap-4;
  }

  &-Header {
    @apply tw:px-6 tw:py-4 tw:border-b tw:border-gray-100 tw:dark:border-gray-700;

    h2 {
      @apply tw:text-lg tw:font-semibold tw:text-gray-900 tw:dark:text-white;
    }
  }

  &-Content {
    @apply tw:p-6;

    &--empty {
      @apply tw:flex tw:flex-col tw:items-center tw:justify-center tw:py-12 tw:text-gray-400 tw:dark:text-gray-500;

      p {
        @apply tw:mt-3 tw:text-center;
      }
    }
  }
}

/* Profile Stats */
.ProfileStat {
  @apply tw:flex tw:flex-col tw:items-center tw:justify-center;

  &-Value {
    @apply tw:text-3xl tw:font-bold tw:text-blue-600 tw:dark:text-blue-400;
  }

  &-Label {
    @apply tw:text-sm tw:text-gray-600 tw:dark:text-gray-400 tw:mt-1;
  }
}

/* Profile Details */
.ProfileDetail {
  @apply tw:flex tw:justify-between tw:py-3 tw:border-b tw:border-gray-100 tw:dark:border-gray-700 tw:last:border-0;

  &-Label {
    @apply tw:text-gray-600 tw:dark:text-gray-400;
  }

  &-Value {
    @apply tw:font-medium tw:text-gray-900 tw:dark:text-white;

    &--success {
      @apply tw:text-green-600 tw:dark:text-green-400;
    }
  }
}
</style>
