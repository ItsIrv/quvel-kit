import { acceptHMRUpdate, defineStore } from 'pinia';
import { INotification } from 'src/modules/Notifications/types/notification.types';
import { Notification } from 'src/modules/Notifications/models/Notification';

/**
 * Interface for the notification state.
 */
interface NotificationState {
  items: Notification[];
  notificationChannel: {
    unsubscribe: () => void;
  } | null;
}

/**
 * Interface for the notification getters.
 */
type NotificationGetters = {
  unreadCount: (state: NotificationState) => number;
};

/**
 * Interface for the notification actions.
 */
interface NotificationActions {
  fetchNotifications(): Promise<void>;
  subscribeToSocket(userId: number): Promise<void>;
  unsubscribeFromSocket(): void;
  push(notification: INotification): void;
  markAllAsRead(): Promise<void>;
}

/**
 * Pinia store for managing notifications.
 */
export const useNotificationStore = defineStore<
  'notifications',
  NotificationState,
  NotificationGetters,
  NotificationActions
>('notifications', {
  state: (): NotificationState => ({
    items: [],
    notificationChannel: null,
  }),

  getters: {
    /**
     * Count of unread notifications.
     */
    unreadCount: (state) => state.items.filter((n) => !n.read_at).length,
  },

  actions: {
    /**
     * Fetch the user's existing notifications from the API.
     */
    async fetchNotifications() {
      try {
        this.items = (
          await this.$container.api.get<{ data: INotification[] }>('/notifications')
        ).data.map((notification) => Notification.fromApi(notification));
      } catch {
        // TODO: Global error handling
      }
    },

    /**
     * Register a socket listener on the user’s private channel.
     */
    async subscribeToSocket(userId: number) {
      if (this.notificationChannel) {
        this.notificationChannel.unsubscribe();
      }

      this.notificationChannel = await this.$container.ws.subscribe({
        channelName: `tenant.${this.$container.config.get('tenantId')}.User.${userId}`,
        type: 'privateNotification',
        callback: (data: INotification) => {
          if (Notification.isModel(data)) {
            this.push(Notification.fromApi(data));
          }
        },
      });
    },

    /**
     * Unsubscribe from the user’s private notification channel.
     */
    unsubscribeFromSocket() {
      this.notificationChannel?.unsubscribe();
    },

    /**
     * Push a new incoming notification.
     */
    push(notification: Notification) {
      this.items.unshift(notification);
    },

    /**
     * Mark all notifications as read.
     */
    async markAllAsRead() {
      await this.$container.api.post('/notifications/mark-all-read');

      this.items.forEach((n) => (n.read_at = new Date().toISOString()));
    },
  },
});

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useNotificationStore, import.meta.hot));
}
