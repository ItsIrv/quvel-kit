<script lang="ts" setup>
import { ref, computed, watch, onMounted } from 'vue';
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

onMounted(() => {
  void fetchTask.run();
});
</script>

<template>
  <div class="relative">
    <q-btn
      block
      flat
      round
      dense
      icon="eva-bell-outline"
      :class="{ 'animate-bell': bellAnimation }"
      :loading="fetchTask.isActive.value"
      :disable="fetchTask.isActive.value"
      @click="toggleDropdown"
    >
      <q-badge
        v-if="unreadCount > 0"
        color="red"
        floating
      >{{ unreadCount }}</q-badge>
    </q-btn>

    <div
      v-if="isDropdownOpen"
      class="DialogGradient GenericBorder MainTransition absolute right-0 mt-2 w-48 rounded-lg p-4 rounded-b-lg z-10"
    >
      <q-btn
        v-if="unreadCount > 0"
        :disable="markAsReadTask.isActive.value"
        :loading="markAsReadTask.isActive.value"
        flat
        :label="$t('notifications.markAllAsRead')"
        class="!w-full block text-blue-5"
        @click="markAllAsRead"
      />

      <q-banner
        v-else-if="notificationStore.items.length === 0"
        inline-actions
        class="bg-transparent"
      >
        {{ $t('notifications.noNotifications') }}
      </q-banner>

      <q-list>
        <q-item
          v-for="notification in notifications"
          :key="notification.id"
        >
          <q-item-section>
            <q-item-label>{{ notification.message }}</q-item-label>
            <q-item-label caption>{{ notification.created_at }}</q-item-label>
            <q-item-label
              :class="notification.read_at ? 'text-green-5' : 'text-red-5'"
              caption
            >
              {{ notification.read_at ? $t('notifications.read') : $t('notifications.unread') }}
            </q-item-label>
          </q-item-section>
        </q-item>
      </q-list>
    </div>
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
