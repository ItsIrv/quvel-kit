<script lang="ts" setup>
import { ref } from 'vue';
import { QMenu, useQuasar } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import NotificationBell from 'src/modules/Notifications/components/NotificationBell.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-left-drawer']);

/**
 * Services
 */
const sessionStore = useSessionStore();
const $q = useQuasar();

/**
 * Refs
 */
const menuRef = ref<QMenu | null>(null);



/**
 * Opens the dropdown menu or left drawer based on platform.
 */
function onDropdownToggle() {
  // On mobile, emit instead
  if ($q.platform.is.desktop) {
    menuRef.value?.toggle();
  } else {
    emits('open-left-drawer');
  }
}
</script>

<template>
  <div
    v-if="sessionStore.isAuthenticated"
    class="tw:flex tw:items-center"
  >
    <div class="tw:relative tw:flex tw:items-center">
      <NotificationBell class="tw:pr-4 tw:hidden tw:sm:!flex" />

      <q-btn
        flat
        no-caps
        class="tw:mr-1 tw:!hidden tw:sm:!flex tw:items-center tw:gap-2 tw:px-2 tw:py-1 tw:rounded-lg tw:hover:bg-gray-100 tw:dark:hover:bg-gray-800 tw:transition-colors"
        @click="onDropdownToggle"
      >
        <span class="tw:text-sm tw:font-medium">{{ sessionStore.user?.name }}</span>
        <q-icon
          name="eva-chevron-down-outline"
          size="16px"
        />
      </q-btn>

      <!-- Mobile User Avatar with Menu -->
      <q-btn
        flat
        round
        dense
        @click="onDropdownToggle"
      >
        <img
          :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'"
          alt="User Avatar"
          class="tw:w-10 tw:h-10 tw:rounded-full tw:border tw:border-stone-400 tw:dark:border-gray-600 tw:shadow-sm"
        />
      </q-btn>
    </div>
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
