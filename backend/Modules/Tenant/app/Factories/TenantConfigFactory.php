<?php

namespace Modules\Tenant\Factories;

use Modules\Tenant\Builders\ConfigSeederBuilder;
use Modules\Tenant\Builders\TenantExclusionBuilder;
use Modules\Tenant\Builders\TenantTableBuilder;
use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;
use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Factory for creating tenant configuration with clean, fluent APIs.
 *
 * Provides the cleanest possible API for modules to configure tenant features.
 */
class TenantConfigFactory
{
    /**
     * Create a tenant table configuration with fluent API.
     *
     * @param string $name Table name
     * @param callable|null $callback Configuration callback
     * @return array Table configuration array
     */
    public static function table(string $name, callable $callback = null): array
    {
        $builder = TenantTableBuilder::create();

        if ($callback) {
            $callback($builder);
        }

        return [$name => $builder->build()];
    }

    /**
     * Create multiple tenant table configurations.
     *
     * @param array $tables Array of table configurations
     * @return array Multiple table configurations
     */
    public static function tables(array $tables): array
    {
        $configs = [];

        foreach ($tables as $name => $callback) {
            if (is_callable($callback)) {
                $configs = array_merge($configs, static::table($name, $callback));
            } else {
                // Support direct TenantTableConfig objects
                $configs[$name] = $callback instanceof TenantTableConfig
                    ? $callback
                    : TenantTableConfig::fromArray($callback);
            }
        }

        return $configs;
    }

    /**
     * Create a tenant configuration seeder with fluent API.
     *
     * @param string $template Template name ('basic', 'isolated', etc.)
     * @param callable $callback Configuration callback
     * @return TenantConfigSeederInterface
     */
    public static function seeder(string $template, callable $callback): TenantConfigSeederInterface
    {
        $builder = ConfigSeederBuilder::create();
        $callback($builder);
        return $builder->build();
    }

    /**
     * Create tenant exclusions configuration with fluent API.
     *
     * @param callable $callback Configuration callback
     * @return TenantExclusionConfig
     */
    public static function exclusions(callable $callback): TenantExclusionConfig
    {
        $builder = TenantExclusionBuilder::create();
        $callback($builder);
        return $builder->build();
    }

    /**
     * Create a simple seeder with just config and visibility.
     *
     * @param array $config Configuration values
     * @param array $visibility Visibility settings
     * @param int $priority Priority level
     * @return TenantConfigSeederInterface
     */
    public static function simpleSeeder(
        array $config,
        array $visibility = [],
        int $priority = 50
    ): TenantConfigSeederInterface {
        return ConfigSeederBuilder::create()
            ->config($config)
            ->visibility($visibility)
            ->priority($priority)
            ->build();
    }

    /**
     * Create exclusions from arrays for convenience.
     *
     * @param array $paths Exact paths to exclude
     * @param array $patterns Path patterns to exclude
     * @return TenantExclusionConfig
     */
    public static function simpleExclusions(array $paths = [], array $patterns = []): TenantExclusionConfig
    {
        return TenantExclusionBuilder::create()
            ->paths($paths)
            ->patterns($patterns)
            ->build();
    }

    /**
     * Create a table configuration from array for backward compatibility.
     *
     * @param string $name Table name
     * @param array $config Table configuration array
     * @return array
     */
    public static function tableFromArray(string $name, array $config): array
    {
        return [$name => TenantTableConfig::fromArray($config)];
    }
}
