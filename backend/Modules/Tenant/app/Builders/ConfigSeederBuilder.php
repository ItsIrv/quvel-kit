<?php

namespace Modules\Tenant\Builders;

use Modules\Tenant\Contracts\ConfigSeederBuilderInterface;
use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\Seeders\GenericTenantSeeder;
use Modules\Tenant\ValueObjects\TenantSeederConfig;

/**
 * Fluent builder for configuration seeder builders.
 * 
 * Provides a clean, chainable API for building tenant configuration seeders.
 */
class ConfigSeederBuilder implements ConfigSeederBuilderInterface
{
    private array $config = [];
    private array $visibility = [];
    private int $priority = 50;

    /**
     * Create a new seeder builder instance.
     *
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Set or merge configuration values.
     *
     * @param array $config Configuration key-value pairs
     * @return static
     */
    public function config(array $config): static
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * Set or merge visibility settings.
     *
     * @param array $visibility Visibility key-value pairs
     * @return static
     */
    public function visibility(array $visibility): static
    {
        $this->visibility = array_merge($this->visibility, $visibility);
        return $this;
    }

    /**
     * Set the priority for this seeder.
     *
     * @param int $priority Priority level (lower runs first)
     * @return static
     */
    public function priority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set multiple configuration values at once.
     *
     * @param array $configs Multiple config arrays to merge
     * @return static
     */
    public function configs(array $configs): static
    {
        foreach ($configs as $config) {
            $this->config($config);
        }
        return $this;
    }

    /**
     * Set a single configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Set visibility for a single configuration key.
     *
     * @param string $key Configuration key
     * @param string $level Visibility level ('public', 'protected', 'private')
     * @return static
     */
    public function visible(string $key, string $level): static
    {
        $this->visibility[$key] = $level;
        return $this;
    }

    /**
     * Mark a configuration key as public.
     *
     * @param string $key Configuration key
     * @return static
     */
    public function public(string $key): static
    {
        return $this->visible($key, 'public');
    }

    /**
     * Mark a configuration key as protected.
     *
     * @param string $key Configuration key
     * @return static
     */
    public function protected(string $key): static
    {
        return $this->visible($key, 'protected');
    }

    /**
     * Mark a configuration key as private.
     *
     * @param string $key Configuration key
     * @return static
     */
    public function private(string $key): static
    {
        return $this->visible($key, 'private');
    }

    /**
     * Build and return the configured seeder.
     *
     * @return TenantConfigSeederInterface
     */
    public function build(): TenantConfigSeederInterface
    {
        $seederConfig = new TenantSeederConfig(
            config: $this->config,
            visibility: $this->visibility,
            priority: $this->priority
        );

        return new GenericTenantSeeder($seederConfig);
    }
}