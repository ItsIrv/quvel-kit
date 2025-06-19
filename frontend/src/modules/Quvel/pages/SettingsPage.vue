<script lang="ts" setup>
import { ref, computed, reactive } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import PageHeroBackground from '../components/Pages/Common/PageHeroBackground.vue';

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
 * Form state
 */
const profileForm = reactive({
  name: user.value?.name || '',
  email: user.value?.email || '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

/**
 * Form validation
 */
const profileErrors = reactive({
  name: '',
  email: '',
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

/**
 * Active tab
 */
const activeTab = ref('profile');

/**
 * Update profile task
 */
const updateProfileTask = task.newTask({
  showNotification: {
    success: () => i18n.t('quvel.settings.success.profileUpdated'),
    error: () => i18n.t('quvel.settings.error.profileUpdateFailed'),
  },
  task: async () => {
    // Reset errors
    Object.keys(profileErrors).forEach(key => {
      profileErrors[key as keyof typeof profileErrors] = '';
    });

    // Validate form
    let isValid = true;

    if (!profileForm.name.trim()) {
      profileErrors.name = i18n.t('quvel.settings.validation.nameRequired');
      isValid = false;
    }

    if (!profileForm.email.trim()) {
      profileErrors.email = i18n.t('quvel.settings.validation.emailRequired');
      isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(profileForm.email)) {
      profileErrors.email = i18n.t('quvel.settings.validation.emailInvalid');
      isValid = false;
    }

    // Password validation only if user is trying to change password
    if (profileForm.newPassword || profileForm.confirmPassword || profileForm.currentPassword) {
      if (!profileForm.currentPassword) {
        profileErrors.currentPassword = i18n.t('quvel.settings.validation.currentPasswordRequired');
        isValid = false;
      }

      if (profileForm.newPassword && profileForm.newPassword.length < 8) {
        profileErrors.newPassword = i18n.t('quvel.settings.validation.passwordLength');
        isValid = false;
      }

      if (profileForm.newPassword !== profileForm.confirmPassword) {
        profileErrors.confirmPassword = i18n.t('quvel.settings.validation.passwordMismatch');
        isValid = false;
      }
    }

    if (!isValid) {
      throw new Error('Validation failed');
    }

    // Here you would make an API call to update the profile
    // For now, we'll simulate a successful update
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Update local user data
    if (sessionStore.getUser) {
      // sessionStore.updateUserData({
      //   ...sessionStore.getUser,
      //   name: profileForm.name,
      //   email: profileForm.email,
      // });
    }

    // Reset password fields
    profileForm.currentPassword = '';
    profileForm.newPassword = '';
    profileForm.confirmPassword = '';
  },
});

/**
 * Notification settings
 */
const notificationSettings = reactive({
  emailNotifications: true,
  pushNotifications: false,
  marketingEmails: false,
});

/**
 * Update notification settings task
 */
const updateNotificationSettingsTask = task.newTask({
  showNotification: {
    success: () => i18n.t('quvel.settings.success.notificationsUpdated'),
    error: () => i18n.t('quvel.settings.error.notificationsUpdateFailed'),
  },
  task: async () => {
    // Here you would make an API call to update notification settings
    // For now, we'll simulate a successful update
    await new Promise(resolve => setTimeout(resolve, 1000));
  },
});

/**
 * Security settings
 */
const securitySettings = reactive({
  twoFactorEnabled: false,
  rememberDevices: true,
});

/**
 * Update security settings task
 */
const updateSecuritySettingsTask = task.newTask({
  showNotification: {
    success: () => i18n.t('quvel.settings.success.securityUpdated'),
    error: () => i18n.t('quvel.settings.error.securityUpdateFailed'),
  },
  task: async () => {
    // Here you would make an API call to update security settings
    // For now, we'll simulate a successful update
    await new Promise(resolve => setTimeout(resolve, 1000));
  },
});
</script>

<template>
  <div class="SettingsPage">
    <PageHeroBackground
      :title="$t('quvel.settings.title')"
      :subtitle="$t('quvel.settings.subtitle')"
    />

    <div class="SettingsContent">
      <div class="SettingsLayout">
        <!-- Sidebar -->
        <div class="SettingsSidebar">
          <div class="SettingsSidebar-Nav">
            <button
              class="SettingsSidebar-NavItem"
              :class="{ 'SettingsSidebar-NavItem--active': activeTab === 'profile' }"
              @click="activeTab = 'profile'"
            >
              <q-icon name="eva-person-outline" />
              <span>{{ $t('quvel.settings.tabs.profile') }}</span>
            </button>

            <button
              class="SettingsSidebar-NavItem"
              :class="{ 'SettingsSidebar-NavItem--active': activeTab === 'notifications' }"
              @click="activeTab = 'notifications'"
            >
              <q-icon name="eva-bell-outline" />
              <span>{{ $t('quvel.settings.tabs.notifications') }}</span>
            </button>

            <button
              class="SettingsSidebar-NavItem"
              :class="{ 'SettingsSidebar-NavItem--active': activeTab === 'security' }"
              @click="activeTab = 'security'"
            >
              <q-icon name="eva-shield-outline" />
              <span>{{ $t('quvel.settings.tabs.security') }}</span>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="SettingsMain">
          <!-- Profile Settings -->
          <div
            v-if="activeTab === 'profile'"
            class="SettingsPanel"
          >
            <div class="SettingsPanel-Header">
              <h2>{{ $t('quvel.settings.profileSettings') }}</h2>
              <p>{{ $t('quvel.settings.profileSettingsDescription') }}</p>
            </div>

            <div class="SettingsPanel-Content">
              <form @submit.prevent="updateProfileTask.run()">
                <div class="SettingsForm-Group">
                  <label for="name">{{ $t('quvel.settings.name') }}</label>
                  <input
                    id="name"
                    v-model="profileForm.name"
                    type="text"
                    :class="{ 'SettingsForm-Input--error': profileErrors.name }"
                    :placeholder="$t('quvel.settings.namePlaceholder')"
                  >
                  <div
                    v-if="profileErrors.name"
                    class="SettingsForm-Error"
                  >
                    {{ profileErrors.name }}
                  </div>
                </div>

                <div class="SettingsForm-Group">
                  <label for="email">{{ $t('quvel.settings.email') }}</label>
                  <input
                    id="email"
                    v-model="profileForm.email"
                    type="email"
                    :class="{ 'SettingsForm-Input--error': profileErrors.email }"
                    :placeholder="$t('quvel.settings.emailPlaceholder')"
                  >
                  <div
                    v-if="profileErrors.email"
                    class="SettingsForm-Error"
                  >
                    {{ profileErrors.email }}
                  </div>
                </div>

                <div class="SettingsForm-Divider">
                  <span>{{ $t('quvel.settings.changePassword') }}</span>
                </div>

                <div class="SettingsForm-Group">
                  <label for="current-password">{{ $t('quvel.settings.currentPassword') }}</label>
                  <input
                    id="current-password"
                    v-model="profileForm.currentPassword"
                    type="password"
                    :class="{ 'SettingsForm-Input--error': profileErrors.currentPassword }"
                    :placeholder="$t('quvel.settings.currentPasswordPlaceholder')"
                  >
                  <div
                    v-if="profileErrors.currentPassword"
                    class="SettingsForm-Error"
                  >
                    {{ profileErrors.currentPassword }}
                  </div>
                </div>

                <div class="SettingsForm-Group">
                  <label for="new-password">{{ $t('quvel.settings.newPassword') }}</label>
                  <input
                    id="new-password"
                    v-model="profileForm.newPassword"
                    type="password"
                    :class="{ 'SettingsForm-Input--error': profileErrors.newPassword }"
                    :placeholder="$t('quvel.settings.newPasswordPlaceholder')"
                  >
                  <div
                    v-if="profileErrors.newPassword"
                    class="SettingsForm-Error"
                  >
                    {{ profileErrors.newPassword }}
                  </div>
                </div>

                <div class="SettingsForm-Group">
                  <label for="confirm-password">{{ $t('quvel.settings.confirmPassword') }}</label>
                  <input
                    id="confirm-password"
                    v-model="profileForm.confirmPassword"
                    type="password"
                    :class="{ 'SettingsForm-Input--error': profileErrors.confirmPassword }"
                    :placeholder="$t('quvel.settings.confirmPasswordPlaceholder')"
                  >
                  <div
                    v-if="profileErrors.confirmPassword"
                    class="SettingsForm-Error"
                  >
                    {{ profileErrors.confirmPassword }}
                  </div>
                </div>

                <div class="SettingsForm-Actions">
                  <q-btn
                    type="submit"
                    color="primary"
                    :loading="updateProfileTask.isActive.value"
                    :label="$t('quvel.settings.saveChanges')"
                  />
                </div>
              </form>
            </div>
          </div>

          <!-- Notification Settings -->
          <div
            v-if="activeTab === 'notifications'"
            class="SettingsPanel"
          >
            <div class="SettingsPanel-Header">
              <h2>{{ $t('quvel.settings.notificationSettings') }}</h2>
              <p>{{ $t('quvel.settings.notificationSettingsDescription') }}</p>
            </div>

            <div class="SettingsPanel-Content">
              <form @submit.prevent="updateNotificationSettingsTask.run()">
                <div class="SettingsForm-Switch">
                  <div class="SettingsForm-SwitchInfo">
                    <label for="email-notifications">{{ $t('quvel.settings.emailNotifications') }}</label>
                    <p>{{ $t('quvel.settings.emailNotificationsDescription') }}</p>
                  </div>
                  <q-toggle
                    id="email-notifications"
                    v-model="notificationSettings.emailNotifications"
                    color="primary"
                  />
                </div>

                <div class="SettingsForm-Switch">
                  <div class="SettingsForm-SwitchInfo">
                    <label for="push-notifications">{{ $t('quvel.settings.pushNotifications') }}</label>
                    <p>{{ $t('quvel.settings.pushNotificationsDescription') }}</p>
                  </div>
                  <q-toggle
                    id="push-notifications"
                    v-model="notificationSettings.pushNotifications"
                    color="primary"
                  />
                </div>

                <div class="SettingsForm-Switch">
                  <div class="SettingsForm-SwitchInfo">
                    <label for="marketing-emails">{{ $t('quvel.settings.marketingEmails') }}</label>
                    <p>{{ $t('quvel.settings.marketingEmailsDescription') }}</p>
                  </div>
                  <q-toggle
                    id="marketing-emails"
                    v-model="notificationSettings.marketingEmails"
                    color="primary"
                  />
                </div>

                <div class="SettingsForm-Actions">
                  <q-btn
                    type="submit"
                    color="primary"
                    :loading="updateNotificationSettingsTask.isActive.value"
                    :label="$t('quvel.settings.saveChanges')"
                  />
                </div>
              </form>
            </div>
          </div>

          <!-- Security Settings -->
          <div
            v-if="activeTab === 'security'"
            class="SettingsPanel"
          >
            <div class="SettingsPanel-Header">
              <h2>{{ $t('quvel.settings.securitySettings') }}</h2>
              <p>{{ $t('quvel.settings.securitySettingsDescription') }}</p>
            </div>

            <div class="SettingsPanel-Content">
              <form @submit.prevent="updateSecuritySettingsTask.run()">
                <div class="SettingsForm-Switch">
                  <div class="SettingsForm-SwitchInfo">
                    <label for="two-factor">{{ $t('quvel.settings.twoFactorAuth') }}</label>
                    <p>{{ $t('quvel.settings.twoFactorAuthDescription') }}</p>
                  </div>
                  <q-toggle
                    id="two-factor"
                    v-model="securitySettings.twoFactorEnabled"
                    color="primary"
                  />
                </div>

                <div class="SettingsForm-Switch">
                  <div class="SettingsForm-SwitchInfo">
                    <label for="remember-devices">{{ $t('quvel.settings.rememberDevices') }}</label>
                    <p>{{ $t('quvel.settings.rememberDevicesDescription') }}</p>
                  </div>
                  <q-toggle
                    id="remember-devices"
                    v-model="securitySettings.rememberDevices"
                    color="primary"
                  />
                </div>

                <div class="SettingsForm-Actions">
                  <q-btn
                    type="submit"
                    color="primary"
                    :loading="updateSecuritySettingsTask.isActive.value"
                    :label="$t('quvel.settings.saveChanges')"
                  />
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
@reference '../../../css/tailwind.scss';

.SettingsPage {
  @apply tw:min-h-screen tw:pb-16;
}

/* Settings Content */
.SettingsContent {
  @apply tw:container tw:mx-auto tw:px-6;
  max-width: 1200px;
}

.SettingsLayout {
  @apply tw:flex tw:flex-col tw:md:flex-row tw:gap-6;
}

/* Sidebar */
.SettingsSidebar {
  @apply tw:w-full tw:md:w-64 tw:shrink-0;

  &-Nav {
    @apply tw:bg-white/80 tw:dark:bg-gray-800/80 tw:rounded-xl tw:overflow-hidden tw:shadow-sm tw:backdrop-blur-sm;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  &-NavItem {
    @apply tw:flex tw:items-center tw:gap-3 tw:w-full tw:px-4 tw:py-3 tw:text-left tw:transition-colors tw:border-l-4 tw:border-transparent;

    &:hover {
      @apply tw:bg-gray-100 tw:dark:bg-gray-700;
    }

    &--active {
      @apply tw:bg-blue-50 tw:dark:bg-blue-900/30 tw:border-l-4 tw:border-blue-500;

      .q-icon {
        @apply tw:text-blue-600 tw:dark:text-blue-400;
      }
    }

    .q-icon {
      @apply tw:text-gray-500 tw:dark:text-gray-400;
    }

    span {
      @apply tw:font-medium tw:text-gray-700 tw:dark:text-gray-200;
    }
  }
}

/* Main Content */
.SettingsMain {
  @apply tw:flex-1;
}

.SettingsPanel {
  @apply tw:bg-white/80 tw:dark:bg-gray-800/80 tw:rounded-xl tw:overflow-hidden tw:shadow-sm tw:backdrop-blur-sm;
  border: 1px solid rgba(255, 255, 255, 0.1);

  &-Header {
    @apply tw:px-6 tw:py-4 tw:border-b tw:border-gray-100 tw:dark:border-gray-700;

    h2 {
      @apply tw:text-xl tw:font-semibold tw:text-gray-900 tw:dark:text-white;
    }

    p {
      @apply tw:text-sm tw:text-gray-600 tw:dark:text-gray-400 tw:mt-1;
    }
  }

  &-Content {
    @apply tw:p-6;
  }
}

/* Form Styles */
.SettingsForm {
  &-Group {
    @apply tw:mb-6;

    label {
      @apply tw:block tw:text-sm tw:font-medium tw:text-gray-700 tw:dark:text-gray-300 tw:mb-1;
    }

    input {
      @apply tw:w-full tw:px-3 tw:py-2 tw:border tw:border-gray-300 tw:dark:border-gray-600 tw:rounded-md tw:bg-white tw:dark:bg-gray-700 tw:text-gray-900 tw:dark:text-white;

      &.SettingsForm-Input--error {
        @apply tw:border-red-500 tw:dark:border-red-500;
      }
    }
  }

  &-Error {
    @apply tw:mt-1 tw:text-sm tw:text-red-600 tw:dark:text-red-400;
  }

  &-Divider {
    @apply tw:relative tw:my-8 tw:border-t tw:border-gray-200 tw:dark:border-gray-700 tw:text-center;

    span {
      @apply tw:relative tw:top-[-12px] tw:bg-white tw:dark:bg-gray-800 tw:px-4 tw:text-sm tw:text-gray-500 tw:dark:text-gray-400;
    }
  }

  &-Switch {
    @apply tw:flex tw:items-center tw:justify-between tw:py-4 tw:border-b tw:border-gray-100 tw:dark:border-gray-700 tw:last:border-0;
  }

  &-SwitchInfo {
    @apply tw:flex-1;

    label {
      @apply tw:block tw:font-medium tw:text-gray-700 tw:dark:text-gray-300;
    }

    p {
      @apply tw:text-sm tw:text-gray-500 tw:dark:text-gray-400 tw:mt-1;
    }
  }

  &-Actions {
    @apply tw:mt-8 tw:flex tw:justify-end;
  }
}
</style>
