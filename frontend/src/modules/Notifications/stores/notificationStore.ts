import { acceptHMRUpdate, defineStore } from 'pinia';
import { INotification } from 'src/modules/Notifications/types/notification.types';

/**
 * Interface for the notification state.
 */
interface NotificationState {
  items: INotification[];
  hasLoaded: boolean;
  userId: number | null;
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
  subscribeToSocket(userId: number): void;
  unsubscribeFromSocket(): void;
  push(notification: INotification): void;
  markAllAsRead(): void;
}

function isNotification(data: unknown): data is INotification {
  return typeof data === 'object' && data !== null && 'message' in data;
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
    hasLoaded: false,
    userId: null,
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
        const { data } = await this.$container.api.get<{ data: INotification[] }>('/notifications');

        this.items = data;
        this.hasLoaded = true;
      } catch {
        // TODO: Global error handling
      }
    },

    /**
     * Register a socket listener on the user’s private channel.
     */
    subscribeToSocket(userId: number) {
      this.userId = userId;
      this.$container.ws.subscribePrivateNotification(`App.Models.User.${userId}`, (data) => {
        if (isNotification(data)) {
          this.push(data);
        }
      });
    },

    /**
     * Unsubscribe from the user’s private notification channel.
     */
    unsubscribeFromSocket() {
      if (this.userId) {
        this.$container.ws.unsubscribe(`App.Models.User.${this.userId}`);
      }
    },

    /**
     * Push a new incoming notification.
     * @param notification - Real-time notification object.
     */
    push(notification: INotification) {
      this.items.unshift(notification);
    },

    /**
     * Mark all notifications as read locally.
     */
    markAllAsRead() {
      this.items = this.items.map((n) => ({
        ...n,
        read_at: n.read_at || new Date().toISOString(),
      }));
    },
  },
});

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useNotificationStore, import.meta.hot));
}
