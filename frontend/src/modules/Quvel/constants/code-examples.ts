/**
 * Code examples
 */
export const CODE_EXAMPLES = {
  tenant: `<?php
// Set the current tenant context
> setTenant(1);
= true

// Get the current tenant
> $tenant = getTenant();
= Modules\\Tenant\\Models\\Tenant {#6974
    id: 1,
    parent_id: null,
    public_id: "01JVE6SRF6BVKJTGXBW71BT5MQ",
    name: "First Tenant - API",
    domain: "api.quvel.127.0.0.1.nip.io",
    config: { ... }
}

// Access tenant configuration
> $appName = $tenant->config->appName;
= "QuVel Local"

> $mailFromAddress = $tenant->config->mailFromAddress;
= "support@quvel.app"

// Tenant scoped services ready to use
> app(FrontendService::class)->redirect('welcome', ['to' => 'quvel'])->getTargetUrl();
= "https://api.quvel.127.0.0.1.nip.io/welcome?to=quvel"
`,

  controller: `<?php
namespace App\\Http\\Controllers;

use Modules\\Tenant\\Contexts\\TenantContext;

class DashboardController extends Controller
{
    public function index(TenantContext $tenantContext)
    {
        $tenant = $tenantContext->get();

        return view('dashboard', [
            'tenant' => $tenant,
            'theme' => $tenantContext->getConfigValue('theme', 'default'),
        ]);
    }
}`,

  model: `<?php
namespace App\\Models;

use Modules\\Tenant\\Traits\\TenantScopedModel;

class Product extends Model
{
    use TenantScopedModel;

    protected $fillable = [
        'name', 'price', 'description'
    ];

    // All queries automatically scoped to current tenant
    // $products = Product::all(); // Only returns current tenant's products
}`,

  services: `// Core Services
import { useContainer } from 'src/modules/Core/composables/useContainer';

// Get services from container
const { api, task, config, i18n, log } = useContainer();

// API calls
api.get('/users');
api.post('/users', { name: 'John' });

// Task management
task.newTask({
  task: () => api.post('/auth/login'),
  showLoading: true
}).run();

// Configuration
const apiTimeout = config.get('api.timeout', 30000);

// Translations
const welcome = i18n.t('auth.welcome');

// Logging
log.info('User action', { userId: 123 });`,

  component: `<script setup lang="ts">
// Basic component with container
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';

// Get what you need
const { api } = useContainer();
const isLoading = ref(false);
const items = ref([]);

// Simple data fetching
async function fetchItems() {
  isLoading.value = true;
  items.value = await api.get('/items');
  isLoading.value = false;
}
</script>

<template>
  <div>
    <button @click="fetchItems">Load</button>
    <div v-for="item in items" :key="item.id">
      {{ item.name }}
    </div>
  </div>
</template>`,

  store: `// Pinia store with container
import { defineStore } from 'pinia';
import { useContainer } from 'src/modules/Core/composables/useContainer';

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    isLoggedIn: false
  }),
  
  actions: {
    async login(email, password) {
      // Access container in actions
      const { api } = useContainer();
      
      const response = await api.post('/login', { 
        email, password 
      });
      
      this.user = response.data.user;
      this.isLoggedIn = true;
    }
  }
});`,
} as const;
