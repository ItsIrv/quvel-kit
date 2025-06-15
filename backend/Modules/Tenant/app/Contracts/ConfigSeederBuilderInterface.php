<?php

namespace Modules\Tenant\Contracts;

/**
 * Contract for fluent configuration seeder builders.
 *
 * Provides a clean, chainable API for building tenant configuration seeders.
 */
interface ConfigSeederBuilderInterface
{
    /**
     * Set or merge configuration values.
     *
     * @param array<string, mixed> $config Configuration key-value pairs
     * @return static
     */
    public function config(array $config): static;

    /**
     * Set or merge visibility settings.
     *
     * @param array<string, mixed> $visibility Visibility key-value pairs
     * @return static
     */
    public function visibility(array $visibility): static;

    /**
     * Set the priority for this seeder.
     *
     * @param int $priority Priority level (lower runs first)
     * @return static
     */
    public function priority(int $priority): static;

    /**
     * Build and return the configured seeder.
     *
     * @return TenantConfigSeederInterface
     */
    public function build(): TenantConfigSeederInterface;
}
