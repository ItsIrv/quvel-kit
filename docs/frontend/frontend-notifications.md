# Notifications Module

## Overview

The Notifications module in QuVel Kit provides a system for managing real-time notifications across the application. It integrates with the WebSocket service to deliver instant notifications and offers a consistent interface for displaying, managing, and interacting with notifications.

## Features

- **Real-time Notifications** – Instant delivery via WebSockets
- **Notification Bell** – UI component for displaying notification count
- **Read/Unread Status** – Tracking of notification status
- **WebSocket Integration** – Private channels for user-specific notifications
- **Tenant-aware** – Notifications are scoped to the current tenant

## Architecture

The Notifications module follows the standard QuVel Kit architecture with these key components:

### Core Components

1. **NotificationService** – Core service for managing notifications
2. **NotificationStore** – Pinia store for notification state management
3. **Notification** – Data model for notification objects
4. **NotificationBell** – UI component for displaying notifications

## Using the Notification System

### Including the Notification Bell

The NotificationBell component can be included in your layout:

```vue
<template>
  <q-layout>
    <q-header>
      <!-- Header content -->
      <NotificationBell />
    </q-header>
    
    <!-- Main content -->
  </q-layout>
</template>

<script setup lang="ts">
import NotificationBell from 'src/modules/Notifications/components/NotificationBell.vue';
</script>
```

### Using the Notification Store

The notification store manages the state of all notifications:

```ts
// Access the notification store
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';

const notificationStore = useNotificationStore();

// Get all notifications
const notifications = notificationStore.items;

// Get unread count
const unreadCount = notificationStore.unreadCount;

// Mark all notifications as read
notificationStore.markAllAsRead();

// Fetch notifications from the API
notificationStore.fetchNotifications();

// Subscribe to real-time notifications
notificationStore.subscribe(userId);

// Unsubscribe from notifications
notificationStore.unsubscribe();
```

## WebSocket Integration

The notification system integrates with the WebSocket service to receive real-time notifications:

```ts
// In a component or store
import { useNotificationStore } from 'src/modules/Notifications/stores/notificationStore';
import { useAuthStore } from 'src/modules/Auth/stores/authStore';

const authStore = useAuthStore();
const notificationStore = useNotificationStore();

// Subscribe to notifications when user logs in
if (authStore.isAuthenticated && authStore.user) {
  notificationStore.subscribe(authStore.user.id);
}

// Unsubscribe when component is unmounted
onUnmounted(() => {
  notificationStore.unsubscribe();
});
```

## Notification Model

Each notification has the following structure:

```ts
interface INotification {
  id: string;            // Unique identifier
  message: string;       // Notification message
  read_at: string | null; // When the notification was read (null if unread)
  created_at: string;    // When the notification was created
  data: Record<string, unknown> | null; // Additional data
}
```

The `Notification` class provides helper methods for working with notification data:

```ts
import { Notification } from 'src/modules/Notifications/models/Notification';

// Create a notification from API data
const notification = Notification.fromApi(apiData);

// Check if an object is a valid notification
if (Notification.isModel(someObject)) {
  // It's a notification
}
```

## Notification Service

The NotificationService handles API communication and WebSocket subscriptions:

```ts
import { NotificationService } from 'src/modules/Notifications/services/NotificationService';
import { useContainer } from 'src/modules/Core/composables/useContainer';

const container = useContainer();
const notificationService = container.get(NotificationService);

// Get notifications from API
const response = await notificationService.getNotifications();

// Mark all notifications as read
await notificationService.markAllAsRead();

// Subscribe to WebSocket notifications
const unsubscribe = await notificationService.subscribeToSocket(
  userId,
  (notification) => {
    // Handle new notification
  }
);

// Later, unsubscribe
unsubscribe?.unsubscribe();
```

## Integration with Backend

The notification system integrates with Laravel's notification system. When a notification is sent from the backend:

```php
// In your Laravel controller
use App\Notifications\DatabaseNotification;

$user->notify(new DatabaseNotification('Your message here'));
```

The notification is broadcast via WebSockets to the user's private channel:

```
tenant.{tenantId}.User.{userId}
```

The frontend automatically receives and displays these notifications.

## Source Files

- **[NotificationService.ts](../../frontend/src/modules/Notifications/services/NotificationService.ts)** – Core notification service
- **[notificationStore.ts](../../frontend/src/modules/Notifications/stores/notificationStore.ts)** – Notification state management
- **[NotificationBell.vue](../../frontend/src/modules/Notifications/components/NotificationBell.vue)** – Notification UI component
- **[notification.types.ts](../../frontend/src/modules/Notifications/types/notification.types.ts)** – Notification type definitions
- **[Notification.ts](../../frontend/src/modules/Notifications/models/Notification.ts)** – Notification model

---

[← Back to Frontend Docs](./README.md)
