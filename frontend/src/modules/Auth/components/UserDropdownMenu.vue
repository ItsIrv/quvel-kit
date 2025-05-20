<script lang="ts" setup>
import { ref, watch } from 'vue';
import { QMenu } from 'quasar';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Props for the UserDropdownMenu component
 */
interface Props {
  /**
   * Reference to the menu element
   */
  menuRef?: QMenu;
}

const props = defineProps<Props>();

/**
 * Local menu reference for SSR safety
 */
const localMenuRef = ref(null);

/**
 * Connect the local menu reference to the parent's menu reference
 */
watch(() => localMenuRef.value, (newVal) => {
  if (props.menuRef && newVal) {
  }
}, { immediate: true });

/**
 * Emits
 */
const emits = defineEmits(['logout']);

/**
 * Services
 */
const { task, i18n } = useContainer();
const sessionStore = useSessionStore();

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
    emits('logout');
  },
});
</script>

<template>
  <q-menu
    ref="localMenuRef"
    anchor="bottom right"
    self="top right"
    class="UserDropdownMenu"
    transition-show="jump-down"
    transition-hide="jump-up"
  >
    <q-card
      class="tw:border tw:border-gray-200 tw:dark:border-gray-700 tw:shadow-lg tw:rounded-lg tw:overflow-hidden tw:min-w-[240px]"
    >
      <!-- User Profile Header -->
      <q-card-section class="tw:bg-gray-50 tw:dark:bg-gray-800 tw:py-3">
        <div class="tw:flex tw:items-center tw:gap-3">
          <img
            :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'"
            alt="User Avatar"
            class="tw:w-10 tw:h-10 tw:rounded-full tw:border tw:border-stone-300 tw:dark:border-gray-600 tw:shadow-sm"
          />
          <div>
            <p class="tw:text-sm tw:font-medium tw:text-gray-900 tw:dark:text-white tw:leading-tight">
              {{ sessionStore.user?.name }}
            </p>
            <p class="tw:text-xs tw:text-gray-500 tw:dark:text-gray-400 tw:leading-tight">
              {{ sessionStore.user?.email }}
            </p>
          </div>
        </div>
      </q-card-section>

      <q-separator />

      <!-- Menu Items -->
      <q-list padding>
        <q-item
          clickable
          v-ripple
          class="tw:py-2"
          to="/profile"
          v-close-popup
        >
          <q-item-section avatar>
            <q-icon
              name="eva-person-outline"
              size="18px"
            />
          </q-item-section>
          <q-item-section>{{ $t('profile.title') }}</q-item-section>
        </q-item>

        <q-item
          clickable
          v-ripple
          class="tw:py-2"
          to="/settings"
          v-close-popup
        >
          <q-item-section avatar>
            <q-icon
              name="eva-settings-2-outline"
              size="18px"
            />
          </q-item-section>
          <q-item-section>{{ $t('settings.title') }}</q-item-section>
        </q-item>

        <q-separator />

        <q-item
          clickable
          v-ripple
          class="tw:py-2 tw:text-red-600 tw:dark:text-red-400"
          :disable="logoutTask.isActive.value"
          @click="logoutTask.run()"
          v-close-popup
        >
          <q-item-section avatar>
            <q-icon
              name="eva-log-out-outline"
              size="18px"
              class="tw:text-red-600 tw:dark:text-red-400"
            />
          </q-item-section>
          <q-item-section>
            <q-spinner
              v-if="logoutTask.isActive.value"
              color="red-6"
              size="1em"
            />
            <span v-else>{{ $t('auth.forms.logout.button') }}</span>
          </q-item-section>
        </q-item>
      </q-list>
    </q-card>
  </q-menu>
</template>

<style lang="scss" scoped>
.UserDropdownMenu {
  border-radius: 8px;
  box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
}
</style>
