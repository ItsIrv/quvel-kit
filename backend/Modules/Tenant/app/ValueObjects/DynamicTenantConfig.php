<?php

namespace Modules\Tenant\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Dynamic tenant configuration that supports partial overrides
 * and module extensions.
 *
 * @implements Arrayable<string, mixed>
 */
class DynamicTenantConfig implements Arrayable
{
    /**
     * The configuration data.
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Visibility settings for configuration keys.
     *
     * @var array<string, TenantConfigVisibility|string>
     */
    protected array $visibility;

    /**
     * Create a new dynamic tenant configuration.
     *
     * @param array<string, mixed> $data
     * @param array<string, TenantConfigVisibility|string> $visibility
     */
    public function __construct(array $data = [], array $visibility = [])
    {
        $this->data       = $data;
        $this->visibility = $visibility;
    }

    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * Set a configuration value using dot notation.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        Arr::set($this->data, $key, $value);
        return $this;
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Remove a configuration key.
     *
     * @param string $key
     * @return static
     */
    public function forget(string $key): static
    {
        Arr::forget($this->data, $key);
        return $this;
    }

    /**
     * Get the visibility for a configuration key.
     *
     * @param string $key
     * @return TenantConfigVisibility
     */
    public function getVisibility(string $key): TenantConfigVisibility
    {
        $visibility = $this->visibility[$key] ?? TenantConfigVisibility::PRIVATE;

        // Handle string values by converting to enum
        if (is_string($visibility)) {
            return TenantConfigVisibility::from($visibility);
        }

        return $visibility;
    }

    /**
     * Set the visibility for a configuration key.
     *
     * @param string $key
     * @param TenantConfigVisibility|string $visibility
     * @return static
     */
    public function setVisibility(string $key, TenantConfigVisibility|string $visibility): static
    {
        $this->visibility[$key] = $visibility;
        return $this;
    }


    /**
     * Merge another configuration into this one.
     *
     * @param DynamicTenantConfig|array<string, mixed> $config
     * @return static
     */
    public function merge(DynamicTenantConfig|array $config): static
    {
        if ($config instanceof DynamicTenantConfig) {
            $this->data       = array_merge($this->data, $config->data);
            $this->visibility = array_merge($this->visibility, $config->visibility);
        } else {
            $this->data = array_merge($this->data, $config);
        }

        return $this;
    }

    /**
     * Get only the configuration values that should be public.
     *
     * @return array<string, mixed>
     */
    public function getPublicConfig(): array
    {
        $public = [];

        foreach ($this->data as $key => $value) {
            if ($this->getVisibility($key) === TenantConfigVisibility::PUBLIC) {
                $public[$key] = $value;
            }
        }

        return $public;
    }

    /**
     * Get configuration values that are public or protected.
     *
     * @return array<string, mixed>
     */
    public function getProtectedConfig(): array
    {
        $protected = [];

        foreach ($this->data as $key => $value) {
            $visibility = $this->getVisibility($key);
            if (
                $visibility === TenantConfigVisibility::PUBLIC ||
                $visibility === TenantConfigVisibility::PROTECTED
            ) {
                $protected[$key] = $value;
            }
        }

        return $protected;
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $config     = $data['config'] ?? [];
        $visibility = $data['visibility'] ?? [];

        // Convert visibility values to enums
        $visibilityEnums = [];
        foreach ($visibility as $key => $value) {
            if (is_string($value)) {
                $visibilityEnums[$key] = TenantConfigVisibility::tryFrom($value) ?? TenantConfigVisibility::PRIVATE;
            } elseif ($value instanceof TenantConfigVisibility) {
                $visibilityEnums[$key] = $value;
            }
        }

        /** @phpstan-ignore-next-line new.static */
        return new static($config, $visibilityEnums);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'config'     => $this->data,
            'visibility' => array_map(
                fn (TenantConfigVisibility|string $v) => $v instanceof TenantConfigVisibility ? $v->value : $v,
                $this->visibility,
            ),
        ];
    }

    /**
     * Magic getter for backward compatibility.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        // First try direct access (for snake_case)
        if ($this->has($name)) {
            return $this->get($name);
        }

        // Convert camelCase to snake_case for backward compatibility
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
        return $this->get($key);
    }

    /**
     * Magic isset for backward compatibility.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        $key = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name);
        return $this->has($key);
    }
}
