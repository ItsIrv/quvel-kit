# WebSockets & Real-Time Communication

## Overview

QuVel Kit provides a robust **WebSocket service** built on **Laravel Echo** and **Pusher**, enabling real-time communication between the server and client. The WebSocket implementation supports public, private, presence, and encrypted channels with a strongly-typed TypeScript interface.

## Features

- **Service Container Integration** – WebSocket service is available through the container as `container.ws`
- **Channel Types** – Support for public, private, presence, and encrypted channels
- **Notification Channels** – Dedicated notification channel types
- **SSR Compatibility** – Automatic detection and handling of server-side rendering
- **TypeScript Integration** – Fully typed API with proper type inference
- **Composable Interface** – Easy-to-use `useWebSockets` composable
- **Debug Tools** – Built-in WebSocket inspector for development

---

## WebSocket Architecture

The WebSocket implementation in QuVel Kit consists of several key components:

1. **WebSocketService** – Core service that manages connections to Laravel Echo/Pusher
2. **useWebSockets Composable** – Vue composable for component-level WebSocket operations
3. **WebSocketChannelManager** – Debug component for inspecting WebSocket connections
4. **Type Definitions** – TypeScript types for channel operations and subscriptions

### Service Registration

The WebSocket service is registered in the service container and automatically handles authentication:

```ts
// Available in the service container
const container = useContainer();
const { ws } = container;
```

---

## Basic Usage

### The `useWebSockets` Composable

The recommended way to use WebSockets in components is through the `useWebSockets` composable:

```ts
import { useWebSockets } from 'src/modules/Core/composables/useWebSockets';
import { onBeforeUnmount } from 'vue';

export default defineComponent({
  setup() {
    const { subscribe, unsubscribe } = useWebSockets();
    let channel;
    
    // Subscribe to a channel when component mounts
    onMounted(async () => {
      channel = await subscribe({
        channelName: 'updates',
        type: 'public',
        event: 'NewUpdate',
        callback: (data) => {
          console.log('New update received:', data);
        }
      });
    });
    
    // Always unsubscribe when component unmounts
    onBeforeUnmount(() => {
      if (channel) {
        unsubscribe(channel);
      }
    });
    
    return {
      // component data
    };
  }
});
```

### Channel Types

QuVel Kit supports several channel types:

#### Public Channels

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

#### Private Channels

Private channels require authentication:

```ts
const privateChannel = await subscribe({
  channelName: `user.${userId}`,  // No need to add 'private-' prefix
  type: 'private',
  event: 'UserNotification',
  callback: (data) => {
    console.log('Private notification received:', data);
  }
});
```

#### Presence Channels

Presence channels track user presence:

```ts
const presenceChannel = await subscribe({
  channelName: `room.${roomId}`,  // No need to add 'presence-' prefix
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

---

## Advanced Usage

### Notification Channels

QuVel Kit provides special channel types for Laravel notifications:

```ts
// Public notification channel
const publicNotificationChannel = await subscribe({
  channelName: 'system-notifications',
  type: 'publicNotification',
  callback: (notification) => {
    console.log('System notification:', notification);
  }
});

// Private notification channel
const privateNotificationChannel = await subscribe({
  channelName: `user.${userId}.notifications`,
  type: 'privateNotification',
  callback: (notification) => {
    console.log('User notification:', notification);
  }
});
```

### Encrypted Channels

For sensitive data, use encrypted channels:

```ts
const encryptedChannel = await subscribe({
  channelName: `secure.${userId}`,
  type: 'encrypted',
  event: 'SecureMessage',
  callback: (data) => {
    console.log('Secure message:', data);
  }
});
```

### Channel Types Reference

| Channel Type | Description | Authentication | Use Case |
|--------------|-------------|----------------|----------|
| `public` | Unauthenticated channel | No | Public updates, general broadcasts |
| `private` | Authenticated user-specific channel | Yes | User-specific data |
| `presence` | Channel with member tracking | Yes | Chat rooms, collaborative features |
| `encrypted` | End-to-end encrypted channel | Yes | Sensitive communications |
| `publicNotification` | Public Laravel notification channel | No | System-wide notifications |
| `privateNotification` | Private Laravel notification channel | Yes | User-specific notifications |

---

## WebSocket Inspector

QuVel Kit includes a built-in WebSocket inspector for debugging:

```ts
// In browser console
window.showWebSocketManager();
```

This opens a dialog that shows:

- Active WebSocket connections
- Channel subscriptions
- Real-time events with data
- Connection status

You can also manually test channels and events through the inspector interface.

---

## Integration with Pinia Stores

WebSockets can be integrated with Pinia stores for state management:

```ts
import { defineStore } from 'pinia';
import { AnyChannel } from 'src/modules/Core/types/websocket.types';

interface StoreState {
  channel: {
    unsubscribe: () => void;
  } | null;
}

export const useExampleStore = defineStore('example', {
  state: (): StoreState => ({
    channel: null,
  }),
  
  actions: {
    async subscribeToChannel(userId: number) {
      // Unsubscribe from existing channel if any
      if (this.channel) {
        this.channel.unsubscribe();
      }
      
      // Subscribe to new channel
      this.channel = await this.$container.ws.subscribe({
        channelName: `user.${userId}`,
        type: 'private',
        event: 'UserUpdate',
        callback: (data) => {
          // Handle the event data
          console.log('User update received:', data);
        },
      });
    },
    
    unsubscribeFromChannel() {
      if (this.channel) {
        this.channel.unsubscribe();
        this.channel = null;
      }
    },
  },
});
```

### Real-World Example: OAuth Flow

The session store uses WebSockets for OAuth authentication flow:

```ts
// From sessionStore.ts
async loginWithOAuth(provider: string, stateless: boolean) {
  // Create a nonce for secure communication
  const { nonce } = await createNonce();
  
  // Subscribe to the OAuth result channel
  this.resultChannel = await this.$container.ws.subscribe({
    channelName: `auth.nonce.${nonce}`,
    type: 'public',
    event: '.oauth.result',
    callback: ({ status }) => {
      // Handle OAuth result
      this.resultChannel!.unsubscribe();
      
      // Process the authentication result
      if (status === OAuthStatusEnum.LOGIN_SUCCESS) {
        // Redeem the nonce and complete authentication
        void this.$container.task.newTask({
          task: async () => await this.$container.api.post(
            `/auth/provider/${provider}/redeem-nonce`,
            { nonce }
          ),
          successHandlers: ({ user }) => {
            this.setSession(user);
          },
        }).run();
      }
    },
  });
  
  // Redirect to OAuth provider
  window.location.href = `${redirectBase}?nonce=${encodeURIComponent(nonce)}`;
}
```

### Best Practices

#### Do's

- **Component Lifecycle** – Always unsubscribe from channels in `onBeforeUnmount`
- **Store Integration** – Use Pinia stores for application-wide WebSocket state
- **Type Safety** – Use TypeScript interfaces for event data
- **Error Handling** – Handle connection failures gracefully
- **Channel Naming** – Follow Laravel Echo channel naming conventions

#### Don'ts

- **Missing Unsubscribe** – Never forget to unsubscribe to prevent memory leaks
- **SSR Conflicts** – Don't try to use WebSockets during SSR, the WebSocketService ignores SSR requests
- **Excessive Subscriptions** – Avoid subscribing to the same channel multiple times
- **Premature Authentication** – Don't subscribe to private channels before authentication

---

## Source Files

- **[WebSocketService.ts](../frontend/src/modules/Core/services/WebSocketService.ts)** – Core WebSocket service
- **[useWebSockets.ts](../frontend/src/modules/Core/composables/useWebSockets.ts)** – WebSocket composable
- **[websocket.types.ts](../frontend/src/modules/Core/types/websocket.types.ts)** – TypeScript types for WebSockets
- **[WebSocketChannelManager.vue](../frontend/src/modules/Core/components/WebSocketChannelManager.vue)** – WebSocket inspector component

---

[← Back to Frontend Docs](./README.MD)
