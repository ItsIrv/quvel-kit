<script lang="ts" setup>
import { ref, watch } from 'vue';
import { useCatalogStore } from 'src/modules/Catalog/stores/catalogStore';
import { useSessionStore } from 'src/modules/Auth/stores/sessionStore';
import { useQuasar } from 'quasar';

defineOptions({
  /**
   * Pre-fetch data for the landing page components.
   */
  async preFetch({ store, ssrContext }) {
    if (ssrContext) {
      await useCatalogStore(store).catalogItemsFetch();
    } else {
      void useCatalogStore(store).catalogItemsFetch();
    }
  },
});

/**
 * Services
 */
const sessionStore = useSessionStore();
const catalogStore = useCatalogStore();

/**
 * State
 */
const activeTab = ref('tenant');

/**
 * Code examples
 */
const codeExamples = {
  tenant: `<?php
// Set the current tenant context
setTenant(1);

// Get the current tenant
$tenant = getTenant();
$tenantName = $tenant->name;
$tenantDomain = $tenant->domain;

// Access tenant configuration
$appUrl = $tenant->config->appUrl;
$mailFromAddress = $tenant->config->mailFromAddress;`,

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
}`
};

/**
 * Lifecycle
 */
watch(
  () => sessionStore.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      void catalogStore.catalogItemsFetch();
    }
  },
);
</script>

<template>
  <div class="LandingPage tw:flex-grow tw:relative tw:overflow-hidden">
    <!-- Hero Content -->
    <div class="tw:container tw:mx-auto tw:px-4 tw:py-16 tw:relative tw:z-10">
      <div class="tw:flex tw:flex-col tw:items-center tw:justify-center tw:text-center tw:mb-12">
        <h1 class="tw:text-4xl tw:md:text-5xl tw:lg:text-6xl tw:font-bold tw:mb-4">
          <span class="hero-gradient-text">QuVel Kit</span>
        </h1>
        <p class="tw:text-xl tw:md:text-2xl tw:text-gray-600 tw:dark:text-gray-300 tw:max-w-3xl">
          Laravel + Vue boilerplate with multi-tenancy, SSR, and Capacitor support
        </p>
      </div>

      <!-- IDE Window -->
      <div
        class="tw:max-w-4xl tw:mx-auto tw:rounded-lg tw:overflow-hidden tw:shadow-2xl tw:border tw:border-gray-200 tw:dark:border-gray-700"
      >
        <!-- IDE Header -->
        <div
          class="tw:bg-gray-100 tw:dark:bg-gray-800 tw:px-4 tw:py-2 tw:flex tw:items-center tw:border-b tw:border-gray-200 tw:dark:border-gray-700"
        >
          <!-- Window Controls -->
          <div class="tw:flex tw:space-x-2 tw:mr-4">
            <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-red-500"></div>
            <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-yellow-500"></div>
            <div class="tw:w-3 tw:h-3 tw:rounded-full tw:bg-green-500"></div>
          </div>

          <!-- Tabs -->
          <div class="tw:flex tw:space-x-1">
            <button
              class="tw:px-3 tw:py-1 tw:text-sm tw:rounded-t-md tw:transition-colors"
              :class="{
                'tw:bg-white tw:dark:bg-gray-900 tw:text-blue-600 tw:dark:text-blue-400': activeTab === 'tenant',
                'tw:text-gray-600 tw:dark:text-gray-400 hover:tw:bg-gray-200 hover:tw:dark:bg-gray-700': activeTab !== 'tenant'
              }"
              @click="activeTab = 'tenant'"
            >
              tinker
            </button>
            <button
              class="tw:px-3 tw:py-1 tw:text-sm tw:rounded-t-md tw:transition-colors"
              :class="{
                'tw:bg-white tw:dark:bg-gray-900 tw:text-blue-600 tw:dark:text-blue-400': activeTab === 'controller',
                'tw:text-gray-600 tw:dark:text-gray-400 hover:tw:bg-gray-200 hover:tw:dark:bg-gray-700': activeTab !== 'controller'
              }"
              @click="activeTab = 'controller'"
            >
              DashboardController.php
            </button>
            <button
              class="tw:px-3 tw:py-1 tw:text-sm tw:rounded-t-md tw:transition-colors"
              :class="{
                'tw:bg-white tw:dark:bg-gray-900 tw:text-blue-600 tw:dark:text-blue-400': activeTab === 'model',
                'tw:text-gray-600 tw:dark:text-gray-400 hover:tw:bg-gray-200 hover:tw:dark:bg-gray-700': activeTab !== 'model'
              }"
              @click="activeTab = 'model'"
            >
              Product.php
            </button>
          </div>
        </div>

        <!-- Code Content -->
        <div class="tw:bg-white tw:dark:bg-gray-900 tw:p-4 tw:overflow-x-auto">
          <pre
            class="tw:text-sm tw:text-gray-800 tw:dark:text-gray-200 tw:font-mono"><code>{{ codeExamples[activeTab] }}</code></pre>
        </div>
      </div>

      <!-- CTA Buttons -->
      <div class="tw:flex tw:justify-center tw:mt-8 tw:space-x-4">
        <q-btn
          color="primary"
          class="tw:px-6 tw:py-2"
          size="lg"
          :to="{ name: 'docs' }"
        >
          Get Started
        </q-btn>
        <q-btn
          outline
          color="primary"
          class="tw:px-6 tw:py-2"
          size="lg"
          href="https://github.com/itsirv/quvel-kit"
          target="_blank"
        >
          <q-icon
            name="eva-github-outline"
            class="tw:mr-2"
          />
          GitHub
        </q-btn>
      </div>
    </div>
  </div>
</template>
