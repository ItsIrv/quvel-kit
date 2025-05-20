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

> config('mail.from.address');
= "support@quvel.app"

// Tenant scoped services ready to use
> app(FrontendService::class)->redirect('welcome', ['to' => 'quvel'])->getTargetUrl();
= "https://quvel.127.0.0.1.nip.io/welcome?to=quvel"
`,

  controller: `<?php
namespace App\\Http\\Controllers;

use Modules\\Tenant\\Contexts\\TenantContext;

class DashboardController extends Controller
{
    // TenantContext scoped per request
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
    // Trait to scope model to current tenant
    use TenantScopedModel;

    protected $fillable = [
        'name', 'price', 'description'
    ];

    // All queries automatically scoped to current tenant
    // $products = Product::all(); // Only returns current tenant's products
}`,

  services: `
// Easily create frontend services with dependencies
export class TestService extends Service implements RegisterService {
  private api!: ApiService;

  // Register service with container
  register({ api, task, i18n, config, log }: ServiceContainer): void {
    this.api = api;
  }

  test(): Promise<void> {
    // Create a task
    this.task.newTask({
      // Only run if tenantId is 1
      shouldRun: () => this.config.get('tenantId') === 1,
      // Task to run
      task: () => this.api.post('/test'),
      // Show notification
      showNotification: {
        success: () => this.i18n.t('common.task.success'),
        error: () => this.i18n.t('common.task.error'),
      },
      // Show a global loading spinner while running
      showLoading: true,
      // Success handlers
      successHandlers: () => {
        this.log.info('Test');
      },
      // Error handlers
      errorHandlers: () => {
        this.log.error('Test');
      },
    }).run();
  }
}
`,

  component: `<script setup lang="ts">
// Basic component with container
import { ref } from 'vue';
import { useContainer } from 'src/modules/Core/composables/useContainer';


// Get services from container
const { api, task, config, i18n, log } = useContainer();

// API calls
api.get('/users');
api.post('/users', { name: 'John' });

// Configuration
const tenantId = config.get('tenantId');

// Translations
const welcome = i18n.t('auth.welcome');

// Logging
log.info('User action', { userId: 123 });

// Task management
const fetchItems = task.newTask({
  shouldRun: () => config.get('tenantId') === 1,
  showNotification: {
    success: () => i18n.t('common.task.success'),
    error: () => i18n.t('common.task.error'),
  },
  task: () => api.get('/items'),
  showLoading: true,
  always: () => {
    // Do something always
  },
  successHandlers: (items: any[]) => {
    // Do something on success
    items.value = items;
  },
  errorHandlers: (error: any) => {
    // Do something on error
    console.error(error);
  },
});

const items = ref([]);
</script>

<template>
  <div>
    <button @click="fetchItems.run" :disabled="fetchItems.isActive">Load</button>
    <div v-for="item in items" :key="item.id">
      {{ item.name }}
    </div>
  </div>
</template>`,

  store: `// Pinia store using pagination and container
import { defineStore } from 'pinia';

interface CatalogState {
  catalogItems: LengthAwareState<CatalogItem>;
}

type CatalogGetters = PaginationGetters<'catalogItems', CatalogItem, LengthAwareState<CatalogItem>>;

type CatalogActions = PaginationActions<'catalogItems', LengthAwarePaginatorResponse<CatalogItem>>;

export const useCatalogStore = defineStore<'catalog', CatalogState, CatalogGetters, CatalogActions>(
  'catalog',
  {
    state: () => ({
      catalogItems: createLengthAwareState<CatalogItem>(),
    }),

    getters: {
      ...createLengthAwareGetters<'catalogItems', CatalogItem>('catalogItems'),
    },

    actions: {
    ...createLengthAwareActions<'catalogItems', CatalogItem>({
      stateKey: 'catalogItems',
      async fetcher(options: PaginationRequest) {
        try {
          return await this.$container.get(CatalogService).fetchCatalogs(options);
        } catch {
          return false;
        }
      },
    }),
  }
);`,
} as const;
