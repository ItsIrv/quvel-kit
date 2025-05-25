<?php

namespace Modules\Tenant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonException;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * @implements CastsAttributes<DynamicTenantConfig|TenantConfig, string|null>
 */
class DynamicTenantConfigCast implements CastsAttributes
{
    /**
     * Cast the stored value into a config object.
     * Supports both legacy TenantConfig and new DynamicTenantConfig.
     *
     * @throws JsonException
     */
    public function get($model, string $key, mixed $value, array $attributes): DynamicTenantConfig|TenantConfig|null
    {
        if (empty($value)) {
            return null;
        }

        $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR) ?? [];

        // Check if this is the new format with 'config' key
        if (isset($data['config']) && is_array($data['config'])) {
            return DynamicTenantConfig::fromArray($data);
        }

        // Legacy format - convert to DynamicTenantConfig
        // First try to create TenantConfig to validate the data
        try {
            $legacyConfig = TenantConfig::fromArray($data);
            // Convert legacy to new format
            $dynamicConfig = new DynamicTenantConfig(
                $legacyConfig->toArray(),
                $data['__visibility'] ?? []
            );
            return $dynamicConfig;
        } catch (\Exception $e) {
            // If it fails, assume it's already in a format we can use
            return new DynamicTenantConfig($data);
        }
    }

    /**
     * Cast the config object back into JSON for storage.
     *
     * @param DynamicTenantConfig|TenantConfig|array<string, mixed>|null $value
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
            $value instanceof TenantConfig        => [
                'config'            => $value->toArray(),
                'visibility'        => $value->visibility ?? [],
                'tier'              => null,
            ],
            is_array($value)                      => $value,
            default                               => throw new \InvalidArgumentException('Invalid tenant config type'),
        };

        /** @var non-empty-string $encoded */
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);

        return $encoded;
    }
}
