<script lang="ts" setup>
import { computed } from 'vue';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';

/**
 * Props
 */
const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true,
  },
});

/**
 * Emits
 */
const emits = defineEmits(['update:modelValue']);

/**
 * Computed
 */
const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emits('update:modelValue', value),
});

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
    emits('update:modelValue', false); // Close the drawer after logout
  },
});
</script>

<template>
  <q-drawer
    v-model="inputValue"
    class="MainGradient"
    side="left"
    overlay
    behavior="mobile"
  >
    <div class="tw:p-6 tw:flex tw:flex-col tw:gap-6">
      <!-- User Info -->
      <div class="tw:flex tw:items-center tw:gap-4">
        <img
          :src="sessionStore.user?.avatarUrl || 'https://api.dicebear.com/7.x/avataaars/svg?seed=44'
            "
          alt="User Avatar"
          class="tw:w-12 tw:h-12 tw:rounded-full tw:border tw:border-stone-400 tw:dark:border-gray-600 tw:shadow-sm"
        />

        <!-- User Name -->
        <div class="tw:flex tw:flex-col">
          <p class="tw:text-lg tw:font-semibold tw:text-gray-900 tw:dark:text-white">
            {{ sessionStore.user?.name }}
          </p>
          <p class="tw:text-sm tw:text-gray-600 tw:dark:text-gray-400">
            {{ sessionStore.user?.email }}
          </p>
        </div>
      </div>

      <q-separator spaced />

      <!-- Navigation Links -->
      <q-list
        bordered
        class="tw:rounded-lg"
      >
        <q-item
          clickable
          v-ripple
          @click="emits('update:modelValue', false)"
        >
          <q-item-section avatar>
            <q-icon name="eva-person-outline" />
          </q-item-section>
          <q-item-section>Profile</q-item-section>
        </q-item>

        <q-item
          clickable
          v-ripple
          @click="emits('update:modelValue', false)"
        >
          <q-item-section avatar>
            <q-icon name="eva-settings-outline" />
          </q-item-section>
          <q-item-section>Settings</q-item-section>
        </q-item>

        <q-item
          clickable
          v-ripple
          :disable="logoutTask.isActive.value"
          @click="logoutTask.run()"
        >
          <q-item-section avatar>
            <q-icon
              name="eva-log-out-outline"
              color="negative"
            />
          </q-item-section>
          <q-item-section class="tw:text-negative">Logout</q-item-section>
        </q-item>
      </q-list>
    </div>
  </q-drawer>
</template>
