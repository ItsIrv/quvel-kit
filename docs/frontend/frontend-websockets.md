# WebSockets & Real-Time Communication

## Overview

QuVel Kit provides a robust **WebSocket service** built on **Laravel Echo** and **Pusher**, enabling real-time communication between the server and client. This architecture supports public, private, presence, and encrypted channels with a strongly-typed interface.

## Features

- **Service Container Integration** – WebSocket service is available through the container as `container.ws`
- **Channel Types** – Support for public, private, presence, and encrypted channels
- **Notification Channels** – Dedicated channels for notifications
- **SSR Compatibility** – Automatic detection and handling of server-side rendering context
- **TypeScript Integration** – Fully typed API for channel subscriptions and events
- **Composable Interface** – Easy-to-use `useWebSockets` composable for Vue components

---

## WebSocket Service Architecture

The WebSocket service connects to Laravel Echo and manages channel subscriptions, authentication, and event handling.

### **Service Registration**

The WebSocket service is registered in the service container:

```ts
// In ServiceContainer.ts
export class ServiceContainer {
  readonly api: ApiService;
  readonly i18n: I18nService;
  readonly validation: ValidationService;
  readonly task: TaskService;
  readonly ws: WebSocketService; // WebSocket service
  
  // ...constructor and other methods
}
```

---

## Using WebSockets in Components

### **The `useWebSockets` Composable**

The recommended way to use WebSockets in components is through the `useWebSockets` composable:

```ts
import { useWebSockets } from 'src/modules/Core/composables/useWebSockets';

const { subscribe, unsubscribe } = useWebSockets();
```

### **Subscribing to a Public Channel**

```ts
const channel = await subscribe({
  channelName: 'updates',
  type: 'public',
  event: 'NewUpdate',
  callback: (data) => {
    console.log('New update received:', data);
  }
});
```

### **Subscribing to a Private Channel**

Private channels require authentication and are prefixed with `private-`:

```ts
const privateChannel = await subscribe({
  channelName: `private-user.${userId}`,
  type: 'private',
  event: 'UserNotification',
  callback: (data) => {
    console.log('Private notification received:', data);
  }
});
```

### **Working with Presence Channels**

Presence channels track user presence and are prefixed with `presence-`:

```ts
const presenceChannel = await subscribe({
  channelName: `presence-room.${roomId}`,
  type: 'presence',
  event: 'NewMessage',
  callback: (message) => {
    console.log('New message:', message);
  },
  presenceHandlers: {
    onHere: (members) => {
      console.log('Current members:', members);
    },
    onJoining: (member) => {
      console.log('Member joined:', member);
    },
    onLeaving: (member) => {
      console.log('Member left:', member);
    }
  }
});
```

### **Unsubscribing from Channels**

Always unsubscribe from channels when they're no longer needed:

```ts
import { onBeforeUnmount } from 'vue';

// In setup function
const channel = await subscribe({ /* options */ });

// Clean up on component unmount
onBeforeUnmount(() => {
  unsubscribe(channel);
});
```

---

## WebSocket Service Implementation

### **Connection Management**

The WebSocket service handles connection lifecycle, including:

- **Lazy Connection** – Only connects when a subscription is requested
- **Authentication** – Automatically authenticates private and presence channels
- **Reconnection** – Handles connection drops and reconnects
- **SSR Detection** – Prevents WebSocket connections during server-side rendering

### **Channel Types**

| Channel Type | Description | Use Case |
|--------------|-------------|----------|
| `public` | Unauthenticated channel | Public updates, general broadcasts |
| `private` | Authenticated user-specific channel | User-specific notifications |
| `presence` | Authenticated channel with member tracking | Chat rooms, collaborative features |
| `encrypted` | Private channel with end-to-end encryption | Sensitive communications |
| `publicNotification` | Public notification channel | System-wide notifications |
| `privateNotification` | Private notification channel | User-specific notifications |

---

## Advanced Usage

### **Integration with Pinia Stores**

WebSockets can be integrated with Pinia stores for state management:

```ts
interface NotificationState {
  notificationChannel: {
    unsubscribe: () => void;
  } | null;
}

type NotificationGetters = {
};

interface NotificationActions {
  subscribeToSocket(userId: number): Promise<void>;
  unsubscribeFromSocket(): void;
}

export const useNotificationStore = defineStore<
  'notifications',
  NotificationState,
  NotificationGetters,
  NotificationActions
>('notifications', {
  state: (): NotificationState => ({
    notificationChannel: null,
  }),
  actions: {
    async subscribeToSocket(userId: number) {
      this.notificationChannel = await this.$container.ws.subscribe({
        channelName: `tenant.${this.$container.config.get('tenant_id')}.User.${userId}`,
        type: 'privateNotification',
        callback: (data: INotification) => {
          // Handle notification
        },
      });
    },
    unsubscribeFromSocket() {
      this.notificationChannel?.unsubscribe();
    },
  },
});
```

---

## Best Practices

- **Component Lifecycle** – Always unsubscribe from channels in `onBeforeUnmount`
- **Error Handling** – Add error handling for WebSocket connection failures
- **Channel Naming** – Follow Laravel Echo channel naming conventions
- **Type Safety** – Leverage TypeScript interfaces for event payloads
- **Connection Management** – Let the service handle connections automatically
- **SSR Considerations** – WebSockets only work on the client side

---

## Related Documentation

- **[Service Container](./frontend-service-container.md)** – Learn about the service container architecture
- **[Component Usage](./frontend-component-usage.md)** – General component usage guidelines
