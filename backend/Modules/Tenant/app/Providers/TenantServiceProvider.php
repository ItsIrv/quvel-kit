<?php

namespace Modules\Tenant\Providers;

use Exception;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\RequestPrivacyService;
use Modules\Tenant\Services\TenantConfigApplier;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;

/**
 * Provider for the Tenant module.
 */
class TenantServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tenant';
    protected string $nameLower = 'tenant';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(TenantFindService::class);
        $this->app->scoped(TenantResolverService::class);
        $this->app->scoped(TenantContext::class);
        $this->app->scoped(RequestPrivacyService::class);

        $this->app->rebinding(Request::class, function (Application $app): void {
            try {
                $tenant = $app->make(abstract: TenantContext::class)->get();

                TenantConfigApplier::apply($tenant, $app->make(ConfigRepository::class));
            } catch (Exception $e) {
                $request = $app->make(Request::class);

                Log::critical(
                    'Tenant Config Could Not Be Applied: ' . $e->getMessage(),
                    [
                        'host'         => $request->getHost(),
                        'customDomain' => $request->headers->get('X-Tenant-Domain'),
                    ],
                );
            }
        });
    }
}
