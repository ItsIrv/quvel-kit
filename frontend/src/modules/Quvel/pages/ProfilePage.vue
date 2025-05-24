<script lang="ts" setup>
import { computed } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Services
 */
const sessionStore = useSessionStore();
const { task, i18n } = useContainer();

/**
 * Computed
 */
const user = computed(() => sessionStore.getUser);

/**
 * Logout Task
 *
 * Handles user logout and updates session state.
 */
const logoutTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedOut'),
  },
  task: async () => {
    await sessionStore.logout();
  },
});
</script>

<template>
  <div class="ProfilePage">
    <!-- Hero Section with User Info -->
    <div class="ProfileHero">
      <div class="ProfileHero-Content">
        <div class="ProfileHero-Avatar">
          <img
            :src="user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'"
            alt="User Avatar"
          />
          <div class="ProfileHero-Status"></div>
        </div>
        
        <div class="ProfileHero-Info">
          <h1>{{ user?.name || $t('quvel.profile.anonymous') }}</h1>
          <p>{{ user?.email }}</p>
          
          <div class="ProfileHero-Actions">
            <q-btn
              flat
              rounded
              color="primary"
              :label="$t('quvel.profile.editProfile')"
              icon="eva-edit-outline"
            />
            <q-btn
              flat
              rounded
              color="negative"
              :label="$t('auth.forms.logout.button')"
              icon="eva-log-out-outline"
              :loading="logoutTask.isActive.value"
              @click="logoutTask.run()"
            />
          </div>
        </div>
      </div>
    </div>
    
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
              <div class="ProfileDetail-Value">{{ user?.role || $t('quvel.profile.defaultRole') }}</div>
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
            <q-icon name="eva-activity-outline" size="48px" />
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

/* Hero Section */
.ProfileHero {
  @apply tw:relative tw:py-16 tw:mb-8;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(37, 99, 235, 0.9));
  
  &::before {
    content: '';
    @apply tw:absolute tw:inset-0 tw:z-0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }
  
  &-Content {
    @apply tw:container tw:mx-auto tw:px-6 tw:relative tw:z-10 tw:flex tw:flex-col tw:md:flex-row tw:items-center tw:gap-8;
    max-width: 1200px;
  }
  
  &-Avatar {
    @apply tw:relative;
    
    img {
      @apply tw:w-32 tw:h-32 tw:md:w-40 tw:md:h-40 tw:rounded-full tw:border-4 tw:border-white tw:shadow-lg;
      object-fit: cover;
    }
    
    .ProfileHero-Status {
      @apply tw:absolute tw:bottom-2 tw:right-2 tw:bg-green-500 tw:rounded-full tw:w-6 tw:h-6 tw:border-2 tw:border-white;
    }
  }
  
  &-Info {
    @apply tw:flex-1 tw:text-white;
    
    h1 {
      @apply tw:text-3xl tw:md:text-4xl tw:font-bold tw:mb-1;
    }
    
    p {
      @apply tw:text-blue-100 tw:mb-6 tw:text-lg;
    }
  }
  
  &-Actions {
    @apply tw:flex tw:gap-3;
    
    .q-btn {
      @apply tw:bg-white/20 tw:backdrop-blur-sm;
      
      &:hover {
        @apply tw:bg-white/30;
      }
    }
  }
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
