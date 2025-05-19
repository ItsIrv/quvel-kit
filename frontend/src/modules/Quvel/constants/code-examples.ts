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
} as const;
