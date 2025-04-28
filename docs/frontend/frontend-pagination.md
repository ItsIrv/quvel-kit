# Pagination

## Overview

QuVel Kit provides a flexible **pagination system** that supports multiple pagination strategies including **length-aware**, **simple**, and **cursor-based** pagination. This system integrates seamlessly with Laravel's pagination responses and Pinia stores.

## Features

- **Multiple Pagination Types** – Support for length-aware, simple, and cursor-based pagination
- **Pinia Store Integration** – Factory functions for creating pagination state, getters, and actions
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

Each pagination type has its own set of helper functions for creating state, actions, and getters in your Pinia stores.

---

## Implementation in Pinia Stores

The pagination system is designed to work seamlessly with Pinia stores. Here's how to implement it:

### 1. Define Your State

```ts
import { defineStore } from 'pinia';
import { createLengthAwareState } from 'src/modules/Core/helpers/Pagination';
import type { Product } from 'src/modules/Catalog/models/Product';

interface ProductState {
  // Create a state for paginated products
  products: LengthAwareState<Product>;
}

export const useProductStore = defineStore('product', {
  state: (): ProductState => ({
    // Initialize pagination state
    products: createLengthAwareState<Product>()
  }),
  
  // Getters and actions will be added next
});
```

### 2. Add Getters

```ts
import { defineStore } from 'pinia';
import {
  createLengthAwareState,
  createLengthAwareGetters
} from 'src/modules/Core/helpers/Pagination';

export const useProductStore = defineStore('product', {
  state: (): ProductState => ({
    products: createLengthAwareState<Product>()
  }),
  
  getters: {
    // Add pagination getters
    ...createLengthAwareGetters<'products', Product>('products')
    // This creates getters like: getProducts, hasProducts
  }
});
```

### 3. Add Actions

```ts
import { defineStore } from 'pinia';
import {
  createLengthAwareState,
  createLengthAwareGetters,
  createLengthAwareActions,
  PaginationRequest
} from 'src/modules/Core/helpers/Pagination';

export const useProductStore = defineStore('product', {
  state: (): ProductState => ({
    products: createLengthAwareState<Product>()
  }),
  
  getters: {
    ...createLengthAwareGetters<'products', Product>('products')
  },
  
  actions: {
    // Add pagination actions
    ...createLengthAwareActions<'products', Product>({
      stateKey: 'products',
      async fetcher(options: PaginationRequest) {
        // This function will be called when pagination actions are triggered
        return await this.$container.api.get('/products', { params: options });
      }
    })
    // This creates actions like: productsFetch, productsNext, productsPrevious, productsReload
  }
});
```

---

## Using Pagination in Components

Once you've set up pagination in your store, you can use it in your components. Here are common patterns:

### Infinite Scrolling

```vue
<script setup lang="ts">
import { useProductStore } from 'src/modules/Catalog/stores/productStore';
import { onMounted } from 'vue';

const productStore = useProductStore();

// Load initial data
onMounted(async () => {
  await productStore.productsFetch();
});

// Load more items
const loadMore = () => {
  productStore.productsNext();
};
</script>

<template>
  <div>
    <div class="product-grid">
      <product-card 
        v-for="product in productStore.getProducts" 
        :key="product.id" 
        :product="product" 
      />
    </div>
    
    <q-btn 
      v-if="productStore.products.hasMore" 
      @click="loadMore" 
      :loading="productStore.products.isLoadingMore"
      class="mt-4"
    >
      Load More
    </q-btn>
    
    <div v-if="productStore.products.isLoadingMore && !productStore.hasProducts" class="loading-indicator">
      Loading...
    </div>
  </div>
</template>
```

### Paginated Navigation

```vue
<script setup lang="ts">
import { useProductStore } from 'src/modules/Catalog/stores/productStore';
import { onMounted } from 'vue';

const productStore = useProductStore();

onMounted(async () => {
  await productStore.productsFetch();
});

async function onPageChange(page: number) {
  await productStore.productsFetch({ page });
}
</script>

<template>
  <div>
    <q-inner-loading :showing="!productStore.hasProducts" />
    
    <div class="product-grid">
      <product-card 
        v-for="product in productStore.getProducts" 
        :key="product.id" 
        :product="product" 
      />
    </div>
    
    <!-- Pagination Controls -->
    <div class="flex justify-center mt-6">
      <q-pagination
        v-if="productStore.hasProducts"
        :model-value="productStore.products.meta?.current_page ?? 0"
        :max="productStore.products.meta?.last_page ?? 0"
        :max-pages="5"
        direction-links
        @update:model-value="onPageChange"
      />
    </div>
  </div>
</template>
```

---

## Pagination Types in Detail

### Length-Aware Pagination

Length-aware pagination includes total count and last page information, making it ideal for traditional pagination with page numbers.

```ts
import { defineStore } from 'pinia';
import { 
  createLengthAwareState, 
  createLengthAwareActions,
  createLengthAwareGetters
} from 'src/modules/Core/helpers/Pagination';

export const useProductStore = defineStore('product', {
  state: () => ({
    products: createLengthAwareState<Product>(),
  }),
  getters: {
    ...createLengthAwareGetters<'products', Product>('products'),
  },
  actions: {
    ...createLengthAwareActions<'products', Product>({
      stateKey: 'products',
      fetcher: async function(options) {
        return await this.$container.api.get('/products', { params: options });
      }
    }),
  }
});
```

### Cursor-Based Pagination

Cursor-based pagination is ideal for infinite scrolling and real-time feeds:

```ts
import { defineStore } from 'pinia';
import { 
  createCursorState, 
  createCursorActions,
  createCursorGetters
} from 'src/modules/Core/helpers/Pagination';

export const useFeedStore = defineStore('feed', {
  state: () => ({
    posts: createCursorState<Post>(),
  }),
  getters: {
    ...createCursorGetters<'posts', Post>('posts'),
  },
  actions: {
    ...createCursorActions<'posts', Post>({
      stateKey: 'posts',
      fetcher: async function(options) {
        // Include cursor in request if available
        const params = this.posts.meta.next_cursor 
          ? { ...options, cursor: this.posts.meta.next_cursor }
          : options;
          
        return await this.$container.api.get('/feed', { params });
      }
    }),
  }
});
```

### Simple Pagination

Simple pagination is useful for large datasets where counting total records is expensive:

```ts
import { defineStore } from 'pinia';
import { 
  createSimpleState, 
  createSimpleActions,
  createSimpleGetters
} from 'src/modules/Core/helpers/Pagination';

export const useLogStore = defineStore('logs', {
  state: () => ({
    logs: createSimpleState<LogEntry>(),
  }),
  getters: {
    ...createSimpleGetters<'logs', LogEntry>('logs'),
  },
  actions: {
    ...createSimpleActions<'logs', LogEntry>({
      stateKey: 'logs',
      fetcher: async function(options) {
        return await this.$container.api.get('/logs', { params: options });
      }
    }),
  }
});
```

---

## Helper Functions

The pagination system includes utility functions to detect pagination types:

```ts
import { 
  isLengthAwarePagination,
  isSimplePagination,
  isCursorPagination
} from 'src/modules/Core/helpers/Pagination';

// Determine pagination type from API response
function handlePaginationResponse(response) {
  if (isLengthAwarePagination(response.meta)) {
    // Handle length-aware pagination
    console.log(`Total records: ${response.meta.total}`);
  } else if (isCursorPagination(response.meta)) {
    // Handle cursor pagination
    console.log(`Next cursor: ${response.meta.next_cursor}`);
  } else if (isSimplePagination(response.meta)) {
    // Handle simple pagination
    console.log(`Current page: ${response.meta.current_page}`);
  }
}
```

---

## Rules and Gotchas

### Common Pitfalls

- **Missing Error Handling** – Always handle API errors in your fetcher function
- **Forgetting to Clean Up** – Reset pagination state when component is unmounted if needed
- **Not Handling Empty States** – Always provide feedback when no data is available
- **Overusing Pagination** – For small datasets, consider loading all data at once

---

## Source Files

- **[Pagination.ts](../../frontend/src/modules/Core/helpers/Pagination.ts)** – Core pagination implementation
- **[catalogStore.ts](../../frontend/src/modules/Catalog/stores/catalogStore.ts)** – Example store implementation
- **[CatalogSection.vue](../../frontend/src/modules/Catalog/components/CatalogSection.vue)** – Example component usage

---

[← Back to Frontend Docs](./README.md)
