<?php

namespace Modules\Tenant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonException;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * @implements CastsAttributes<DynamicTenantConfig, string|null>
 */
class DynamicTenantConfigCast implements CastsAttributes
{
    /**
     * Cast the stored value into a config object.
     * Supports both legacy TenantConfig and new DynamicTenantConfig.
     *
     * @throws JsonException
     */
    public function get($model, string $key, mixed $value, array $attributes): DynamicTenantConfig|null
    {
        if (empty($value)) {
            return null;
        }

        $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR) ?? [];

        // Check if this is the format with 'config' key
        if (isset($data['config']) && is_array($data['config'])) {
            return DynamicTenantConfig::fromArray($data);
        }

        // Assume it's a direct config array
        return new DynamicTenantConfig($data);
    }

    /**
     * Cast the config object back into JSON for storage.
     *
     * @param DynamicTenantConfig|array<string, mixed>|null $value
     *
     * @throws JsonException
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $data = match (true) {
            $value instanceof DynamicTenantConfig => $value->toArray(),
            is_array($value)                      => $value,
            default                               => throw new \InvalidArgumentException('Invalid tenant config type'),
        };

        /** @var non-empty-string $encoded */
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);

        return $encoded;
    }
}
