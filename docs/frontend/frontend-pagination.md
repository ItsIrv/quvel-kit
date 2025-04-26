# Pagination Strategies

## Overview

QuVel Kit provides a comprehensive **pagination system** that supports multiple pagination strategies including **length-aware**, **simple**, and **cursor-based** pagination. This system integrates seamlessly with Laravel's pagination responses and Pinia stores.

## Features

- **Multiple Pagination Types** – Support for length-aware, simple, and cursor-based pagination
- **Pinia Store Integration** – Factory functions for creating pagination state and actions
- **Type Safety** – Fully typed interfaces for all pagination components
- **Laravel Compatibility** – Designed to work with Laravel's pagination responses
- **Automatic State Management** – Handles loading states, page tracking, and data merging

---

## Pagination Types

QuVel Kit supports three pagination strategies:

| Type | Description | Use Case |
|------|-------------|----------|
| **Length-Aware** | Includes total count and last page information | When total counts are needed for UI elements like page numbers |
| **Simple** | Only tracks current page and next/previous | For large datasets where counting total records is expensive |
| **Cursor** | Uses cursor tokens instead of page numbers | For infinite scrolling and real-time data feeds |

---

## Setting Up Pagination in a Pinia Store

### **1. Create Pagination State**

First, define your pagination state in a Pinia store:

```ts
import { defineStore } from 'pinia';
import { createLengthAwareState } from 'src/modules/Core/helpers/Pagination';
import type { User } from 'src/modules/User/models/User';

export const useUserStore = defineStore('user', {
  state: () => ({
    // Create a length-aware paginated state for users
    users: createLengthAwareState<User>(),
    
    // Other state properties...
  }),
  
  // Actions and getters will be added next
});
```

### **2. Create Pagination Actions**

Add pagination actions to your store:

```ts
import { defineStore } from 'pinia';
import { 
  createLengthAwareState, 
  createLengthAwareActions 
} from 'src/modules/Core/helpers/Pagination';
import type { User } from 'src/modules/User/models/User';
import type { LengthAwarePaginatorResponse } from 'src/modules/Core/types/laravel.types';

export const useUserStore = defineStore('user', {
  state: () => ({
    users: createLengthAwareState<User>(),
  }),
  
  actions: {
    // Spread the generated pagination actions into your store
    ...createLengthAwareActions<'users', User>({
      stateKey: 'users',
      fetcher: async function(options = {}) {
        // Use the container to make API requests
        return await this.$container.api.get<LengthAwarePaginatorResponse<User>>(
          '/api/users',
          { params: options }
        );
      }
    }),
    
    // You can add additional custom actions
    async searchUsers(query: string) {
      // This will use the generated usersFetch action
      await this.usersFetch({ search: query });
    }
  }
});
```

### **3. Create Pagination Getters**

Add getters to easily access your paginated data:

```ts
import { defineStore } from 'pinia';
import { 
  createLengthAwareState, 
  createLengthAwareActions,
  createLengthAwareGetters
} from 'src/modules/Core/helpers/Pagination';
import type { User } from 'src/modules/User/models/User';

export const useUserStore = defineStore('user', {
  state: () => ({
    users: createLengthAwareState<User>(),
  }),
  
  actions: {
    // Pagination actions...
  },
  
  getters: {
    // Spread the generated pagination getters
    ...createLengthAwareGetters<'users', User>('users'),
    
    // Add custom getters if needed
    activeUsers() {
      return this.getUsers.filter(user => user.isActive);
    }
  }
});
```

---

## Using Pagination in Vue Components

### **Example: User List with Pagination**

```vue
<script setup lang="ts">
import { useUserStore } from 'src/modules/User/stores/userStore';
import { onMounted } from 'vue';

const userStore = useUserStore();

// Load initial data
onMounted(async () => {
  await userStore.usersFetch();
});

// Load next page
const loadMore = () => {
  userStore.usersNext();
};

// Reload data
const refresh = () => {
  userStore.usersReload();
};
</script>

<template>
  <div>
    <h1>Users</h1>
    
    <q-btn @click="refresh">Refresh</q-btn>
    
    <div v-if="userStore.users.isLoadingMore" class="loading">
      Loading...
    </div>
    
    <ul v-if="userStore.hasUsers">
      <li v-for="user in userStore.getUsers" :key="user.id">
        {{ user.name }}
      </li>
    </ul>
    
    <div v-else-if="!userStore.users.isLoadingMore">
      No users found.
    </div>
    
    <q-btn 
      v-if="userStore.users.hasMore" 
      @click="loadMore" 
      :loading="userStore.users.isLoadingMore"
    >
      Load More
    </q-btn>
    
    <div v-if="userStore.users.meta.total">
      Showing {{ userStore.users.data.length }} of {{ userStore.users.meta.total }} users
    </div>
  </div>
</template>
```

---

## Cursor-Based Pagination

For infinite scrolling or real-time feeds, cursor-based pagination is often more efficient:

```ts
import { defineStore } from 'pinia';
import { 
  createCursorState, 
  createCursorActions,
  createCursorGetters
} from 'src/modules/Core/helpers/Pagination';
import type { Post } from 'src/modules/Post/models/Post';

export const usePostStore = defineStore('post', {
  state: () => ({
    posts: createCursorState<Post>(),
  }),
  
  actions: {
    ...createCursorActions<'posts', Post>({
      stateKey: 'posts',
      fetcher: async function(options = {}) {
        // If we have a next cursor, include it in the request
        const params = this.posts.meta.next_cursor 
          ? { ...options, cursor: this.posts.meta.next_cursor }
          : options;
          
        return await this.$container.api.get('/api/posts', { params });
      }
    }),
  },
  
  getters: {
    ...createCursorGetters<'posts', Post>('posts'),
  }
});
```

---

## Utility Functions

QuVel Kit provides utility functions to detect pagination types:

```ts
import { 
  isLengthAwarePagination,
  isSimplePagination,
  isCursorPagination
} from 'src/modules/Core/helpers/Pagination';

// Determine pagination type from API response
function handlePaginationResponse(response) {
  if (isLengthAwarePagination(response.meta)) {
    console.log(`Total records: ${response.meta.total}`);
  } else if (isCursorPagination(response.meta)) {
    console.log(`Next cursor: ${response.meta.next_cursor}`);
  } else if (isSimplePagination(response.meta)) {
    console.log(`Current page: ${response.meta.current_page}`);
  }
}
```

---

## Best Practices

- **Choose the Right Pagination Type** – Use length-aware for smaller datasets, cursor-based for infinite scrolling
- **Handle Loading States** – Always show loading indicators during pagination operations
- **Implement Error Handling** – Add error handling for failed pagination requests
- **Clear Data When Needed** – Use the `clearPrevious` parameter to reset data when changing filters
- **Optimize API Requests** – Only request the data you need from the backend
- **Type Safety** – Define proper interfaces for your paginated models

---

## Related Documentation

- **[Service Container](./frontend-service-container.md)** – Learn about the service container architecture
- **[Component Usage](./frontend-component-usage.md)** – General component usage guidelines
