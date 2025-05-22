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
      <span
        v-if="unreadCount > 0"
        class="NotificationBell-Badge"
      >{{ unreadCount }}</span>
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

<style scoped></style>
