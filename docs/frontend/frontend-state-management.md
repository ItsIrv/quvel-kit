# State & Data Management

## Overview

QuVel Kit uses **Pinia** for state management, enabling **type-safe**, **modular** state across your application. The framework provides enhanced Pinia stores with built-in integration for the **Service Container**, **WebSockets**, and **pagination helpers** to simplify common data management tasks.

## Features

- **TypeScript Integration** – Fully typed state, getters, and actions.
- **Service Container Plugin** – Direct access to services within stores.
- **WebSocket Support** – Easily subscribe to real-time events.
- **Pagination Helpers** – Simplified data fetching with pagination support.
- **SSR Support** – State is properly hydrated between server and client.
- **Hot Module Replacement** – Stores update without page refresh during development.

---

## Creating a Pinia Store

QuVel Kit encourages a type-safe approach to store creation using TypeScript interfaces:

```ts
import { defineStore } from 'pinia';

// 1. Define your state interface
interface ProductState {
  items: Product[];
  selectedId: number | null;
  isLoading: boolean;
}

// 2. Define your getters type
type ProductGetters = {
  selectedProduct: (state: ProductState) => Product | undefined;
  totalProducts: (state: ProductState) => number;
};

// 3. Define your actions interface
interface ProductActions {
  fetchProducts(): Promise<void>;
  selectProduct(id: number): void;
  createProduct(product: Partial<Product>): Promise<void>;
}

// 4. Create the store with full type safety
export const useProductStore = defineStore<
  'products',
  ProductState,
  ProductGetters,
  ProductActions
>('products', {
  state: (): ProductState => ({
    items: [],
    selectedId: null,
    isLoading: false
  }),

  getters: {
    selectedProduct: (state) => 
      state.items.find(item => item.id === state.selectedId),
    totalProducts: (state) => state.items.length
  },

  actions: {
    async fetchProducts() {
      this.isLoading = true;
      try {
        const { data } = await this.$container.api.get<{ data: Product[] }>('/products');
        this.items = data;
      } catch (error) {
        // Error handling
      } finally {
        this.isLoading = false;
      }
    },

    selectProduct(id: number) {
      this.selectedId = id;
    },

    async createProduct(product: Partial<Product>) {
      const { data } = await this.$container.api.post<{ data: Product }>('/products', product);
      this.items.push(data);
    }
  }
});

// 5. Enable Hot Module Replacement
if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useProductStore, import.meta.hot));
}
```

---

## Using the Service Container in Stores

All Pinia stores in QuVel Kit have **direct access** to the **Service Container** through the `this.$container` property, which is injected by a custom Pinia plugin.

### Accessing Core Services

```ts
import { defineStore } from 'pinia';

export const useProductStore = defineStore('product', {
  actions: {
    async fetchProducts() {
      // Access the API service
      const { data } = await this.$container.api.get('/products');
      
      // Access the validation service
      const isValid = this.$container.validation.validate(data, productSchema);
      
      // Access the task service
      const task = this.$container.task.newTask({
        task: async () => this.processData(data),
        showLoading: true
      });
      
      await task.run();
    }
  }
});
```

### Accessing Custom Services

```ts
import { defineStore } from 'pinia';
import { CatalogService } from 'src/modules/Catalog/services/CatalogService';

export const useCatalogStore = defineStore('catalog', {
  actions: {
    async fetchCatalogs() {
      // Get a custom service from the container
      const catalogService = this.$container.getService<CatalogService>('catalog');
      
      // Use the service
      const catalogs = await catalogService.fetchCatalogs();
      
      return catalogs;
    }
  }
});
```

---

## WebSocket Integration in Stores

QuVel Kit makes it easy to integrate WebSockets with your Pinia stores for real-time updates:

```ts
import { defineStore } from 'pinia';
import { INotification } from 'src/modules/Notifications/types/notification.types';

interface NotificationState {
  items: Notification[];
  notificationChannel: { unsubscribe: () => void } | null;
}

export const useNotificationStore = defineStore('notifications', {
  state: (): NotificationState => ({
    items: [],
    notificationChannel: null
  }),
  
  actions: {
    // Subscribe to real-time notifications
    async subscribeToSocket(userId: number) {
      // Clean up existing subscription if any
      if (this.notificationChannel) {
        this.notificationChannel.unsubscribe();
      }

      // Create a new subscription
      this.notificationChannel = await this.$container.ws.subscribe({
        channelName: `tenant.${this.$container.config.get('tenant_id')}.User.${userId}`,
        type: 'privateNotification',
        callback: (data: INotification) => {
          // Handle incoming notification
          this.push(data);
        }
      });
    },
    
    // Clean up subscription
    unsubscribeFromSocket() {
      this.notificationChannel?.unsubscribe();
      this.notificationChannel = null;
    },
    
    // Add a new notification to the store
    push(notification: INotification) {
      this.items.unshift(notification);
    }
  }
});
```

For more details on WebSockets, see the [WebSockets documentation](./frontend-websockets.md).

---

[← Back to Frontend Docs](./README.MD)
