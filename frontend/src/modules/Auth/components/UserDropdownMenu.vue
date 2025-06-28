<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Props
 */
interface Props {
  /**
   * Whether the menu is open
   */
  modelValue?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue', 'close']);

/**
 * State
 */
const isOpen = ref(false);

/**
 * Watch for changes to the model value
 */
watch(() => props.modelValue, (newVal) => {
  isOpen.value = newVal;
});

/**
 * Watch for changes to the isOpen value
 */
watch(() => isOpen.value, (newVal) => {
  emits('update:modelValue', newVal);
});

/**
 * Services
 */
const { task, i18n } = useContainer();
const router = useRouter();
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
    emits('close');
    await router.push('/');
  },
});
</script>

<template>
  <q-menu
    v-model="isOpen"
    anchor="bottom right"
    self="top right"
    class="UserDropdownMenu"
    transition-show="jump-down"
    transition-hide="jump-up"
    persistent
    :auto-close="false"
    no-parent-event
    no-focus
    @hide="emits('close')"
  >
    <div class="tw:overflow-hidden tw:min-w-[240px]">
      <!-- User Profile Header -->
      <q-card-section class="tw:bg-gray-50 tw:dark:bg-gray-800 tw:py-3 tw:rounded-t-lg">
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
          class="tw:py-2 tw:text-red-600 tw:dark:text-red-400"
          :disable="logoutTask.isActive.value"
          @click="logoutTask.run()"
        >
          <q-item-section avatar>
            <q-icon
              name="eva-log-out-outline"
              size="18px"
              class="tw:text-red-600 tw:dark:text-red-400"
            />
          </q-item-section>

          <q-item-section>
            <q-spinner v-if="logoutTask.isActive.value" />
            <span v-else>{{ $t('auth.forms.logout.button') }}</span>
          </q-item-section>

        </q-item>
      </q-list>
    </div>
  </q-menu>
</template>

<style lang="scss" scoped>
.UserDropdownMenu {
  border-radius: 8px;
  box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
}
</style>
