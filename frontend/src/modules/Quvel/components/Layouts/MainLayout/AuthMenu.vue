<script lang="ts" setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import BackInOutUp from 'src/modules/Core/components/Transitions/BackInOutUp.vue';
import NotificationBell from 'src/modules/Notifications/components/NotificationBell.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-left-drawer']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();
const $q = useQuasar();

/**
 * Refs
 */
const isDropdownOpen = ref(false);

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

    isDropdownOpen.value = false;
  },
});

/**
 * Opens the dropdown menu.
 */
function onDropdownToggle() {
  // On mobile, emit instead
  if ($q.platform.is.desktop) {
    isDropdownOpen.value = !isDropdownOpen.value;
  } else {
    emits('open-left-drawer');
  }
}
</script>

<template>
  <div
    v-if="sessionStore.isAuthenticated"
    class="tw:relative"
  >
    <div class="tw:row tw:items-center">
      <NotificationBell class="tw:pr-8 tw:hidden tw:sm:!flex" />

      <span
        class="tw:mr-2 tw:text-xl tw:font-bold tw:hidden tw:sm:!flex tw:cursor-pointer"
        @click="onDropdownToggle"
      >
        {{ sessionStore.user?.name }}
      </span>

      <!-- User Avatar -->
      <q-btn
        flat
        round
        dense
        @click="onDropdownToggle"
      >
        <img
          :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'
            "
          alt="User Avatar"
          class="tw:w-10 tw:h-10 tw:rounded-full tw:border tw:border-stone-400 tw:dark:border-gray-600 tw:shadow-sm"
        />
      </q-btn>
    </div>

    <!-- Dropdown Menu -->
    <BackInOutUp>
      <div
        v-if="isDropdownOpen"
        class="UserDropdown"
      >
        <!-- User Information -->
        <p class="tw:text-sm tw:text-gray-900 tw:dark:text-white tw:font-semibold">
          {{ sessionStore.user?.name }}
        </p>

        <p class="tw:text-xs tw:text-gray-600 tw:dark:text-gray-400 tw:mb-2">{{ sessionStore.user?.email }}</p>

        <!-- Logout Button -->
        <q-btn
          color="negative"
          class="tw:block tw:!w-full"
          flat
          :label="$t('auth.forms.logout.button')"
          :loading="logoutTask.isActive.value"
          @click="logoutTask.run()"
        />
      </div>
    </BackInOutUp>
  </div>

  <template v-else>
    <!-- Login Button -->
    <q-btn
      :ripple="false"
      class="PrimaryButton tw:hidden tw:sm:!flex"
      unelevated
      @click="emits('login-click')"
    >
      {{ $t('auth.forms.login.button') }}
    </q-btn>
  </template>
</template>
