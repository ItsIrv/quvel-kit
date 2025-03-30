<script lang="ts" setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import { useSessionStore } from 'src/stores/sessionStore';
import { useContainer } from 'src/composables/useContainer';
import BackInOutUp from 'src/components/Transitions/BackInOutUp.vue';

/**
 * Emits
 */
const emits = defineEmits(['login-click', 'open-left-drawer']);

/**
 * Services
 */
const container = useContainer();
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
const logoutTask = container.task.newTask({
  showNotification: {
    success: container.i18n.t('auth.status.success.loggedOut'),
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
  <div v-if="sessionStore.isAuthenticated" class="relative">
    <div class="row items-center">
      <span class="mr-6 text-xl font-bold hidden sm:!flex cursor-pointer" @click="onDropdownToggle">
        {{ sessionStore.user?.name }}
      </span>

      <!-- User Avatar -->
      <q-btn flat round dense @click="onDropdownToggle">
        <img
          :src="
            sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'
          "
          alt="User Avatar"
          class="w-10 h-10 rounded-full border border-stone-400 dark:border-gray-600 shadow-sm"
        />
      </q-btn>
    </div>

    <!-- Dropdown Menu -->
    <BackInOutUp>
      <div v-if="isDropdownOpen" class="UserDropdown">
        <!-- User Information -->
        <p class="text-sm text-gray-900 dark:text-white font-semibold">
          {{ sessionStore.user?.name }}
        </p>

        <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ sessionStore.user?.email }}</p>

        <!-- Logout Button -->
        <q-btn
          color="negative"
          class="block !w-full"
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
    <q-btn :ripple="false" class="PrimaryButton" unelevated @click="emits('login-click')">
      Login
    </q-btn>
  </template>
</template>
