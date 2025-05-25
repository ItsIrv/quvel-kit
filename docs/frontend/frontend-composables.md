# Core Composables

The Core module provides a set of Vue 3 composables that enable universal/isomorphic functionality across server-side rendering (SSR) and client environments. These composables follow the Composition API pattern and handle common concerns like dependency injection, runtime detection, and external integrations.

## Table of Contents

- [useClient](#useclient) - Client-side detection
- [useContainer](#usecontainer) - Service container access
- [useMetaConfig](#usemetaconfig) - SEO and metadata management
- [useQueryMessageHandler](#usequerymessagehandler) - URL query message processing
- [useRecaptcha](#userecaptcha) - Google reCAPTCHA v3 integration
- [useScopedService](#usescopedservice) - Component-scoped service instances
- [useScript](#usescript) - Dynamic script loading
- [useUrlQueryHandler](#useurlqueryhandler) - URL parameter extraction and processing
- [useWebSockets](#usewebsockets) - WebSocket channel management
- [useXsrf](#usexsrf) - CSRF token handling

## useClient

A composable that provides client-side detection, particularly useful in universal/isomorphic code that needs to behave differently in SSR versus browser environments.

### Example

```typescript
<script setup lang="ts">
import { useClient } from 'src/modules/Core/composables/useClient';

const { isClient } = useClient();
</script>

<template>
  <div v-if="isClient">
    <p>Client-side content</p>
  </div>
</template>
```

## useContainer

Provides access to the application's service container, which manages dependency injection. The composable automatically handles the differences between SSR and client environments.

### Example

```typescript
<script setup lang="ts">
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { onMounted } from 'vue';

// Access services from the container
const { api, config, i18n } = useContainer();

onMounted(async () => {
  // Use the API service to fetch data
  try {
    const response = await api.get('/api/products');
    console.log('Products:', response.data);
  } catch (error) {
    console.error('Failed to fetch products:', error);
  }
  
  // Access configuration values
  const apiTimeout = config.get('api.timeout', 30000);
  console.log(`API timeout: ${apiTimeout}ms`);
});
</script>
```

## useMetaConfig

Simplifies SEO and metadata management by providing a consistent way to configure page titles, meta tags, and structured data.

### Example

```typescript
<script setup lang="ts">
import { useMetaConfig } from 'src/modules/Core/composables/useMetaConfig';

// Basic usage with just a page title
useMetaConfig('Product Catalog');

// Advanced usage with custom metadata
useMetaConfig('User Dashboard', {
  meta: {
    description: {
      name: 'description',
      content: 'Manage your account, view orders, and update preferences',
    },
    ogImage: { 
      property: 'og:image', 
      content: 'https://example.com/dashboard-preview.jpg' 
    },
  },
  // Add custom structured data for rich results
  script: {
    structuredData: {
      type: 'application/ld+json',
      innerHTML: JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: 'User Dashboard',
        description: 'Manage your account and preferences',
      }),
    },
  },
});
</script>
```

## useQueryMessageHandler

Processes URL query parameters to display notifications or messages, commonly used after redirects from authentication flows, form submissions, or other actions that result in a page change.

### Example

```typescript
<script setup lang="ts">
import { useQueryMessageHandler } from 'src/modules/Core/composables/useQueryMessageHandler';

// Basic usage - will look for ?message=some.message.key in the URL
// and display a notification using the i18n translation
useQueryMessageHandler();

// Advanced usage with custom options
useQueryMessageHandler({
  // Use 'status' as the query parameter instead of 'message'
  key: 'status',
  
  // Add a prefix to the translation key
  i18nPrefix: 'auth.status.',
  
  // Custom notification timeout
  timeout: 5000,
  
  // Custom function to determine notification type
  type: (status) => {
    if (status.includes('success')) return 'positive';
    if (status.includes('warning')) return 'warning';
    if (status.includes('error')) return 'negative';
    return 'info';
  }
});

// Example URL that would trigger a notification:
// https://example.com/dashboard?status=password_reset_success
// This would look up the i18n key 'auth.status.password_reset_success'
</script>
```

## useRecaptcha

Integrates Google reCAPTCHA v3 for invisible bot protection, managing script loading, token generation, and cleanup.

### Example

```typescript
<script setup lang="ts">
import { useRecaptcha } from 'src/modules/Core/composables/useRecaptcha';
import { ref } from 'vue';

const { execute, isLoaded, error } = useRecaptcha();
const isSubmitting = ref(false);

async function handleFormSubmit() {
  isSubmitting.value = true;
  
  try {
    // Get a reCAPTCHA token for the 'login' action
    const recaptchaToken = await execute('login');
    
    // Include the token with your form submission
    const formData = {
      email: 'user@example.com',
      password: '********',
      recaptchaToken
    };
    
    // Send the data to your API
    await api.post('/auth/login', formData);
    
    // Handle successful login
  } catch (err) {
    // Handle errors
    console.error('Login failed:', err);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <form @submit.prevent="handleFormSubmit">
    <!-- Form fields -->
    <button type="submit" :disabled="isSubmitting || !isLoaded">
      {{ isSubmitting ? 'Logging in...' : 'Login' }}
    </button>
    
    <div v-if="error" class="error-message">
      Failed to load reCAPTCHA. Please refresh the page.
    </div>
  </form>
</template>
```

## useScopedService

Creates component-scoped service instances that are automatically cleaned up when the component is unmounted, preventing memory leaks and ensuring proper resource management.

### Example

```typescript
<script setup lang="ts">
import { useScopedService } from 'src/modules/Core/composables/useScopedService';
import { ProductService } from 'src/modules/Catalog/services/ProductService';
import { onMounted, ref } from 'vue';

// Get a scoped instance of ProductService
const productService = useScopedService(ProductService);
const products = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  try {
    // The service instance is tied to this component's lifecycle
    products.value = await productService.getProducts({ 
      category: 'electronics',
      sort: 'price_asc'
    });
  } catch (error) {
    console.error('Failed to fetch products:', error);
  } finally {
    isLoading.value = false;
  }
});

// When the component is unmounted, the service will be automatically
// removed from the container, allowing any resources it holds to be
// garbage collected
</script>
```

## useScript

Provides a clean interface for dynamically loading, unloading, and managing external JavaScript scripts in Vue components.

### Example

```typescript
<script setup lang="ts">
import { useScript } from 'src/modules/Core/composables/useScript';
import { ref, watch } from 'vue';

// Initialize the script loader for a payment gateway SDK
const { isLoaded, isLoading, error, load, unload } = useScript(
  'payment-sdk',
  'https://js.payment-provider.com/sdk.js',
  {
    // Don't load automatically, wait until needed
    autoLoad: false,
    
    // Clean up when component is unmounted
    autoUnload: true,
    
    // Add attributes to the script tag
    attributes: {
      'data-client-id': 'your-client-id',
      'data-currency': 'USD'
    }
  }
);

const showPaymentForm = ref(false);
const paymentInitialized = ref(false);

// Load the payment SDK when the payment form is shown
watch(showPaymentForm, async (show) => {
  if (show && !isLoaded.value && !isLoading.value) {
    try {
      await load();
      initializePayment();
    } catch (err) {
      console.error('Failed to load payment SDK:', err);
    }
  }
});

function initializePayment() {
  if (isLoaded.value && window.PaymentSDK) {
    window.PaymentSDK.initialize({
      container: '#payment-container',
      onSuccess: handlePaymentSuccess,
      onError: handlePaymentError
    });
    paymentInitialized.value = true;
  }
}

function handlePaymentSuccess(result) {
  // Process successful payment
}

function handlePaymentError(error) {
  // Handle payment error
}
</script>

<template>
  <div>
    <button @click="showPaymentForm = !showPaymentForm">
      {{ showPaymentForm ? 'Hide' : 'Show' }} Payment Form
    </button>
    
    <div v-if="showPaymentForm">
      <div v-if="isLoading">Loading payment form...</div>
      <div v-else-if="error">Failed to load payment form: {{ error.message }}</div>
      <div v-else-if="isLoaded && paymentInitialized" id="payment-container"></div>
    </div>
  </div>
</template>
```

## useUrlQueryHandler

Extracts, validates, and processes URL query parameters for handling various URL-based flows like authentication tokens, password resets, and invitation links.

### Example

```typescript
<script setup lang="ts">
import { useUrlQueryHandler } from 'src/modules/Core/composables/useUrlQueryHandler';
import { ref } from 'vue';

const invitationAccepted = ref(false);

// Handle team invitation flow
const { extractedParams, checkUrl } = useUrlQueryHandler({
  // Parameters to extract from URL
  params: ['team_id', 'invitation_token', 'email'],
  
  // Validate that all required parameters are present
  validate: (params) => {
    return !!params.team_id && 
           !!params.invitation_token && 
           !!params.email;
  },
  
  // Remove parameters from URL after processing
  cleanupUrl: true,
  
  // Process the invitation when parameters are found
  onMatch: async (params) => {
    try {
      // Accept the invitation using the extracted parameters
      await api.post('/teams/accept-invitation', {
        teamId: params.team_id,
        token: params.invitation_token,
        email: params.email
      });
      
      invitationAccepted.value = true;
      
      // Show success message
      showNotification('positive', 'You have successfully joined the team!');
    } catch (error) {
      // Handle invitation error
      showNotification('negative', 'Failed to accept invitation. It may be invalid or expired.');
    }
  }
});

// Example URL that would trigger the handler:
// https://example.com/dashboard?team_id=123&invitation_token=abc123&email=user@example.com
</script>

<template>
  <div>
    <div v-if="invitationAccepted" class="success-message">
      You have successfully joined the team!
    </div>
    
    <!-- Rest of the dashboard content -->
  </div>
</template>
```

## useWebSockets

Manages WebSocket connections and channels for real-time communication, supporting various channel types (public, private, presence, encrypted).

### Example

```typescript
<script setup lang="ts">
import { useWebSockets } from 'src/modules/Core/composables/useWebSockets';
import { ref, onBeforeUnmount } from 'vue';

const { subscribe, unsubscribe } = useWebSockets();
const messages = ref([]);
const activeChannels = ref([]);

// Subscribe to a public channel
async function subscribeToPublicChannel() {
  const channel = await subscribe({
    type: 'public',
    name: 'announcements',
    events: {
      'new-announcement': (data) => {
        messages.value.push({
          type: 'announcement',
          content: data.message,
          timestamp: new Date()
        });
      }
    }
  });
  
  activeChannels.value.push(channel);
}

// Subscribe to a private user-specific channel
async function subscribeToPrivateChannel(userId) {
  const channel = await subscribe({
    type: 'private',
    name: `user.${userId}`,
    events: {
      'notification': (data) => {
        messages.value.push({
          type: 'notification',
          content: data.message,
          timestamp: new Date()
        });
      },
      'message': (data) => {
        messages.value.push({
          type: 'message',
          sender: data.sender,
          content: data.content,
          timestamp: new Date(data.sent_at)
        });
      }
    }
  });
  
  activeChannels.value.push(channel);
}

// Subscribe to a presence channel for a team
async function subscribeToPresenceChannel(teamId) {
  const channel = await subscribe({
    type: 'presence',
    name: `team.${teamId}`,
    events: {
      'task-assigned': (data) => {
        messages.value.push({
          type: 'task',
          taskId: data.task_id,
          title: data.title,
          timestamp: new Date()
        });
      }
    },
    // Presence channel specific handlers
    here: (members) => {
      console.log('Members currently online:', members);
    },
    joining: (member) => {
      console.log('Member joined:', member);
    },
    leaving: (member) => {
      console.log('Member left:', member);
    }
  });
  
  activeChannels.value.push(channel);
}

// Initialize channels
onMounted(async () => {
  await subscribeToPublicChannel();
  
  if (userStore.isAuthenticated) {
    await subscribeToPrivateChannel(userStore.user.id);
    
    if (userStore.user.teamId) {
      await subscribeToPresenceChannel(userStore.user.teamId);
    }
  }
});

// Clean up all channels when component is unmounted
onBeforeUnmount(() => {
  activeChannels.value.forEach(channel => {
    unsubscribe(channel);
  });
  activeChannels.value = [];
});
</script>
```

## useXsrf

Ensures the CSRF token is set for authenticated requests, particularly important for applications using Laravel Sanctum for authentication.

### Example

```typescript
<script setup lang="ts">
import { useXsrf } from 'src/modules/Core/composables/useXsrf';
import { useContainer } from 'src/modules/Core/composables/useContainer';
import { ref } from 'vue';

// Initialize XSRF token handling
useXsrf();

const { api } = useContainer();
const isSubmitting = ref(false);
const errorMessage = ref('');

async function submitForm(formData) {
  isSubmitting.value = true;
  errorMessage.value = '';
  
  try {
    // The XSRF token will be automatically included in the request
    // thanks to the useXsrf composable
    const response = await api.post('/api/profile/update', formData);
    
    // Handle successful response
    showNotification('positive', 'Profile updated successfully');
  } catch (error) {
    // Handle error
    errorMessage.value = error.response?.data?.message || 'An error occurred';
    showNotification('negative', errorMessage.value);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <form @submit.prevent="submitForm(formData)">
    <!-- Form fields -->
    
    <div v-if="errorMessage" class="error-message">
      {{ errorMessage }}
    </div>
    
    <button type="submit" :disabled="isSubmitting">
      {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
    </button>
  </form>
</template>
```

## Best Practices

When using these composables, follow these guidelines:

1. **SSR Awareness**: Always consider both SSR and client environments when using these composables. Use `useClient()` to conditionally execute browser-only code.

2. **Cleanup**: Ensure resources are properly cleaned up, especially for subscriptions and event listeners. Composables like `useScopedService` and `useScript` handle this automatically.

3. **Error Handling**: Always implement proper error handling, especially for asynchronous operations like API calls, script loading, and WebSocket connections.

4. **Typing**: Leverage TypeScript for better type safety and developer experience. All composables are fully typed.

5. **Composition**: Combine multiple composables to build complex functionality. For example, use `useContainer` with `useScopedService` for component-specific service instances.

6. **Lazy Loading**: For performance optimization, load external resources (scripts, WebSocket connections) only when needed.

7. **Security**: Use `useXsrf` for CSRF protection and `useRecaptcha` for bot protection on sensitive forms.
