<script lang="ts" setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { task } = useContainer();
const notificationStore = useNotificationStore();

const markAsReadTask = task.newTask({
  task: async () => {
    await notificationStore.markAllAsRead();
  },
});

const fetchTask = task.newTask({
  task: async () => {
    await notificationStore.fetchNotifications();
  },
});

const isDropdownOpen = ref(false);
const bellAnimation = ref(false);
const notificationRef = ref<HTMLElement | null>(null);

const notifications = computed(() => notificationStore.items);
const unreadCount = computed(() => notificationStore.unreadCount);

function toggleDropdown(event: Event) {
  // Prevent event propagation to avoid immediate closing
  event.stopPropagation();
  isDropdownOpen.value = !isDropdownOpen.value;
}

function markAllAsRead() {
  void markAsReadTask.run();
}

function closeDropdown() {
  isDropdownOpen.value = false;
}

// Handle clicks outside to close the dropdown
function handleClickOutside() {
  if (notificationRef.value) {
    closeDropdown();
  }
}

// Add and remove event listeners
onMounted(() => {
  void fetchTask.run();
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Watch for new notifications to trigger animation
watch(
  () => notificationStore.unreadCount,
  (newCount, oldCount) => {
    // Trigger animation only if new unread notifications are added
    if (newCount > oldCount && newCount > 0) {
      bellAnimation.value = true;

      setTimeout(() => {
        bellAnimation.value = false;
      }, 500);
    }
  }
);


</script>

<template>
  <div
    ref="notificationRef"
    class="tw:relative"
  >
    <button
      class="ide-dropdown__action"
      :class="{ 'animate-bell': bellAnimation }"
      :disabled="fetchTask.isActive.value"
      @click="toggleDropdown"
    >
      <q-icon
        name="eva-bell-outline"
        size="20px"
      />
      <span
        v-if="unreadCount > 0"
        class="ide-badge"
      >{{ unreadCount }}</span>
    </button>

    <q-menu
      v-model="isDropdownOpen"
      anchor="bottom right"
      self="top right"
      class="UserDropdownMenu"
      transition-show="jump-down"
      transition-hide="jump-up"
      persistent
      :auto-close="false"
      no-parent-event
      no-focus
    >
      <div class="tw:overflow-hidden tw:min-w-[240px]">
        <!-- Header Section -->
        <div
          class="tw:bg-gray-50 tw:dark:bg-gray-800 tw:py-3 tw:px-4 tw:rounded-t-lg tw:border-b tw:border-gray-200 tw:dark:border-gray-700"
        >
          <div class="tw:flex tw:items-center tw:justify-between">
            <h3 class="tw:!text-2xl tw:text-sm tw:font-medium tw:text-gray-900 tw:dark:text-white">
              {{ $t('notifications.title') }}
            </h3>
            <span class="tw:text-xs tw:font-medium tw:text-gray-500 tw:dark:text-gray-400">
              {{ notifications.length }} {{ $t('notifications.total') }}
            </span>
          </div>
        </div>

        <!-- Content Section -->
        <div class="tw:max-h-[300px] tw:overflow-y-auto">
          <!-- Mark as Read Button -->
          <div
            v-if="unreadCount > 0"
            class="tw:p-3 tw:border-b tw:border-gray-200 tw:dark:border-gray-700"
          >
            <q-btn
              :disable="markAsReadTask.isActive.value"
              :loading="markAsReadTask.isActive.value"
              flat
              size="sm"
              :label="$t('notifications.markAllAsRead')"
              class="tw:!w-full tw:block tw:text-blue-500 tw:dark:text-blue-400"
              @click="markAllAsRead"
            />
          </div>

          <!-- Empty State -->
          <div
            v-if="notifications.length === 0"
            class="tw:p-4 tw:text-center tw:text-gray-500 tw:dark:text-gray-400"
          >
            <q-icon
              name="eva-bell-off-outline"
              size="24px"
              class="tw:mb-2"
            />
            <p>{{ $t('notifications.noNotifications') }}</p>
          </div>

          <!-- Notification List -->
          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="tw:p-3 tw:border-b tw:border-gray-200 tw:dark:border-gray-700 hover:tw:bg-gray-50 tw:dark:hover:tw:bg-gray-800"
            >
              <div class="tw:flex tw:items-start tw:gap-3">
                <div class="tw:flex-1">
                  <p class="tw:text-sm tw:text-gray-700 tw:dark:text-gray-300">{{ notification.message }}</p>
                  <div class="tw:flex tw:items-center tw:mt-1 tw:gap-2">
                    <span class="tw:text-xs tw:text-gray-500 tw:dark:text-gray-400">{{ notification.created_at }}</span>
                    <span
                      :class="notification.read_at ? 'tw:bg-green-100 tw:text-green-800 tw:dark:bg-green-900/30 tw:dark:text-green-400' : 'tw:bg-red-100 tw:text-red-800 tw:dark:bg-red-900/30 tw:dark:text-red-400'"
                      class="tw:text-xs tw:px-1.5 tw:py-0.5 tw:rounded-full"
                    >
                      {{ notification.read_at ? $t('notifications.read') : $t('notifications.unread') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </q-menu>
  </div>
</template>

<style scoped>
@keyframes bell-shake {

  from,
  to {
    transform: translate3d(0, 0, 0);
  }

  10%,
  30%,
  50%,
  70%,
  90% {
    transform: translate3d(-5px, 0, 0);
  }

  20%,
  40%,
  60%,
  80% {
    transform: translate3d(5px, 0, 0);
  }
}

.animate-bell {
  animation: bell-shake 0.5s ease-in-out;
}
</style>
