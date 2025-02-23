<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\app\Http\Middleware\TenantMiddleware;
use Modules\Tenant\app\Services\TenantFindService;
use Modules\Tenant\app\Services\TenantResolverService;
use Modules\Tenant\app\Services\TenantSessionService;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TenantServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Tenant';

    protected string $nameLower = 'tenant';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(
            module_path($this->name, 'database/migrations'),
        );

        // Register middleware
        Route::aliasMiddleware('tenant', TenantMiddleware::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(
            TenantSessionService::class,
            function ($app): TenantSessionService {
                return new TenantSessionService(
                    $app->make(Session::class),
                );
            }
        );

        // Register Tenant Services
        $this->app->singleton(
            TenantResolverService::class,
            function ($app): TenantResolverService {
                return new TenantResolverService(
                    $app->make(TenantFindService::class),
                    $app->make(TenantSessionService::class),
                );
            }
        );

        $this->app->singleton(
            TenantFindService::class,
            function ($app): TenantFindService {
                return new TenantFindService();
            }
        );
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->name, 'lang'),
                $this->nameLower,
            );

            $this->loadJsonTranslationsFrom(
                module_path($this->name, 'lang'),
            );
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath         = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($configPath),
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace(
                        $configPath . DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname(),
                    );

                    $configKey = $this->nameLower . '.' . str_replace(
                        [DIRECTORY_SEPARATOR, '.php'],
                        ['.', ''],
                        $relativePath,
                    );

                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes(
                        [$file->getPathname() => config_path($relativePath)],
                        'config',
                    );

                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath   = resource_path('views/modules/' . $this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes(
            [$sourcePath => $viewPath],
            ['views', "{$this->nameLower}-module-views"],
        );

        $this->loadViewsFrom(
            array_merge(
                $this->getPublishableViewPaths(),
                [$sourcePath],
            ),
            $this->nameLower,
        );

        $componentNamespace = $this->module_namespace(
            $this->name,
            $this->app_path(
                config('modules.paths.generator.component-class.path'),
            ),
        );

        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths     = [];
        $viewPaths = config('view.paths');
        foreach (is_array($viewPaths) ? $viewPaths : [] as $path) {
            if (is_dir("$path/modules/{$this->nameLower}")) {
                $paths[] = "$path/modules/{$this->nameLower}";
            }
        }

        return $paths;
    }
}
