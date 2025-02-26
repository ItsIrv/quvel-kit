<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ModuleServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Tenant';

    protected string $nameLower = 'tenant';

    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(
            module_path($this->name, 'database/migrations'),
        );
    }

    /**
     * Register the middleware.
     */
    public function registerMiddleware(): void
    {
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path("lang/modules/{$this->nameLower}");

        if ($this->isDir($langPath)) {
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
    public function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath         = module_path($this->name, $relativeConfigPath);

        if ($this->isDir($configPath)) {
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

                    assert(is_string($relativePath));
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
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the view paths for the module.
     *
     * @return string[]
     */
    public function getPublishableViewPaths(): array
    {
        $paths     = [];
        $viewPaths = config('view.paths');
        $viewPaths = is_array($viewPaths) ? $viewPaths : [];

        foreach ($viewPaths as $path) {
            if ($this->isDir("$path/modules/{$this->nameLower}")) {
                $paths[] = "$path/modules/{$this->nameLower}";
            }
        }

        return $paths;
    }

    public function isDir(string $path): bool
    {
        return is_dir($path);
    }
}
