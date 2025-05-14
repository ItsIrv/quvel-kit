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
  () => notificationStore.items.length,
  (newLength, oldLength) => {
    if (newLength > oldLength) {
      bellAnimation.value = true;

      setTimeout(() => {
        bellAnimation.value = false;
      }, 500); // Duration of the animation
    }
  },
);

onMounted(() => {
  void notificationStore.fetchNotifications();
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
        label="Mark all as read"
        class="!w-full block bg-amber-2"
        @click="markAllAsRead"
      />

      <q-banner
        v-else-if="notificationStore.items.length === 0"
        inline-actions
        class="bg-transparent"
      >
        No notifications.
      </q-banner>

      <q-list>
        <q-item
          v-for="notification in notifications"
          :key="notification.id"
        >
          <q-item-section>
            <q-item-label>{{ notification.message }}</q-item-label>
            <q-item-label caption>{{ notification.created_at }}</q-item-label>
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
