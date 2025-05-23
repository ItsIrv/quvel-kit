<script lang="ts" setup>
import { ref, computed, watch } from 'vue';
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

const notifications = computed(() => notificationStore.items);
const unreadCount = computed(() => notificationStore.unreadCount);

function toggleDropdown() {
  isDropdownOpen.value = !isDropdownOpen.value;
}

function markAllAsRead() {
  void markAsReadTask.run();
}

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
  <div class="NotificationBell">
    <button
      class="NotificationBell-Button"
      :class="{ 'NotificationBell-Button--animated': bellAnimation }"
      :disabled="fetchTask.isActive.value"
      @click="toggleDropdown"
    >
      <q-icon
        name="eva-bell-outline"
        size="20px"
      />

      <q-badge
        class="NotificationBell-Badge"
        color="red"
        floating
      >
        {{ unreadCount }}
      </q-badge>
    </button>

    <q-menu
      v-model="isDropdownOpen"
      anchor="bottom right"
      self="top right"
      class="NotificationBell-Menu"
      transition-show="jump-down"
      transition-hide="jump-up"
      persistent
      :auto-close="false"
      no-parent-event
      no-focus
    >
      <div class="NotificationBell-Container">
        <!-- Header Section -->
        <div class="NotificationBell-Header">
          <div class="NotificationBell-HeaderContent">
            <h3 class="NotificationBell-Title">
              {{ $t('notifications.title') }}
            </h3>
            <span class="NotificationBell-Count">
              {{ notifications.length }} {{ $t('notifications.total') }}
            </span>
          </div>
        </div>

        <!-- Content Section -->
        <div class="NotificationBell-Content">
          <!-- Mark as Read Button -->
          <div
            v-if="unreadCount > 0"
            class="NotificationBell-MarkAllSection"
          >
            <q-btn
              :disable="markAsReadTask.isActive.value"
              :loading="markAsReadTask.isActive.value"
              flat
              size="sm"
              :label="$t('notifications.markAllAsRead')"
              class="NotificationBell-MarkAllButton"
              @click="markAllAsRead"
            />
          </div>

          <!-- Empty State -->
          <div
            v-if="notifications.length === 0"
            class="NotificationBell-EmptyState"
          >
            <q-icon
              name="eva-bell-off-outline"
              size="24px"
              class="NotificationBell-EmptyIcon"
            />
            <p>{{ $t('notifications.noNotifications') }}</p>
          </div>

          <!-- Notification List -->
          <div v-else>
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="NotificationBell-Item"
            >
              <div class="NotificationBell-ItemContent">
                <div class="NotificationBell-ItemBody">
                  <p class="NotificationBell-Message">{{ notification.message }}</p>
                  <div class="NotificationBell-ItemMeta">
                    <span class="NotificationBell-Date">{{ notification.created_at }}</span>
                    <span
                      :class="notification.read_at ? 'NotificationBell-Status--read' : 'NotificationBell-Status--unread'"
                      class="NotificationBell-Status"
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

<style lang="scss">
@reference '../../../css/tailwind.scss';

// NotificationBell Component
.NotificationBell {
  @apply tw:relative;

  &-Button {
    @apply tw:flex tw:items-center tw:justify-center tw:w-8 tw:h-8 tw:rounded-full tw:bg-gray-100 tw:dark:bg-gray-800 tw:text-gray-600 tw:dark:text-gray-400 tw:transition-all tw:duration-200 tw:ease-in-out tw:cursor-pointer;

    &:hover {
      @apply tw:bg-blue-500/10 tw:text-blue-500;
    }

    &--animated {
      animation: bell-shake 0.5s ease-in-out;
    }
  }

  // Bell shake animation
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

  &-Badge {}

  &-Menu {
    @apply tw:z-50;
  }

  &-Container {
    @apply tw:overflow-hidden tw:min-w-[240px];
  }

  &-Header {
    @apply tw:bg-gray-50 tw:dark:bg-gray-800 tw:py-3 tw:px-4 tw:rounded-t-lg tw:border-b tw:border-gray-200 tw:dark:border-gray-700;
  }

  &-HeaderContent {
    @apply tw:flex tw:items-center tw:justify-between;
  }

  &-Title {
    @apply tw:text-sm tw:font-medium tw:text-gray-900 tw:dark:text-white;
  }

  &-Count {
    @apply tw:text-xs tw:font-medium tw:text-gray-500 tw:dark:text-gray-400;
  }

  &-Content {
    @apply tw:max-h-[300px] tw:overflow-y-auto;
  }

  &-MarkAllSection {
    @apply tw:p-3 tw:border-b tw:border-gray-200 tw:dark:border-gray-700;
  }

  &-MarkAllButton {
    @apply tw:w-full tw:block tw:text-blue-500 tw:dark:text-blue-400;
  }

  &-EmptyState {
    @apply tw:p-4 tw:text-center tw:text-gray-500 tw:dark:text-gray-400;
  }

  &-EmptyIcon {
    @apply tw:mb-2;
  }

  &-Item {
    @apply tw:p-3 tw:border-b tw:border-gray-200 tw:dark:border-gray-700 tw:hover:bg-gray-50 tw:dark:hover:bg-gray-800;
  }

  &-ItemContent {
    @apply tw:flex tw:items-start tw:gap-3;
  }

  &-ItemBody {
    @apply tw:flex-1;
  }

  &-Message {
    @apply tw:text-sm tw:text-gray-700 tw:dark:text-gray-300;
  }

  &-ItemMeta {
    @apply tw:flex tw:items-center tw:mt-1 tw:gap-2;
  }

  &-Date {
    @apply tw:text-xs tw:text-gray-500 tw:dark:text-gray-400;
  }

  &-Status {
    @apply tw:text-xs tw:px-1.5 tw:py-0.5 tw:rounded-full;

    &--read {
      @apply tw:bg-green-100 tw:text-green-800 tw:dark:bg-green-900/30 tw:dark:text-green-400;
    }

    &--unread {
      @apply tw:bg-red-100 tw:text-red-800 tw:dark:bg-red-900/30 tw:dark:text-red-400;
    }
  }
}
</style>
