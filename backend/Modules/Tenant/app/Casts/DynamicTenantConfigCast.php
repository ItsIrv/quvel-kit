<?php

namespace Modules\Tenant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonException;
use Modules\Tenant\Enums\TenantConfigVisibility;
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
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR) ?? [];

        // Check if this is the format with 'config' key
        if (isset($data['config']) && is_array($data['config'])) {
            return DynamicTenantConfig::fromArray($data);
        }

        // Check if it has __visibility key (legacy format)
        if (isset($data['__visibility'])) {
            $visibility = [];
            foreach ($data['__visibility'] as $visKey => $vis) {
                $visibility[$visKey] = is_string($vis)
                    ? TenantConfigVisibility::tryFrom($vis) ?? TenantConfigVisibility::PRIVATE
                    : $vis;
            }
            unset($data['__visibility']);
            return new DynamicTenantConfig($data, $visibility);
        }

        // Assume it's a direct config array
        return new DynamicTenantConfig($data);
    }

    /**
     * Cast the config object back into JSON for storage.
     *
     * @throws JsonException
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        /** @phpstan-ignore-next-line instanceof.alwaysFalse */
        if ($value instanceof DynamicTenantConfig) {
            $data = $value->toArray();
        } else {
            $data = $value;
        }

        /** @var non-empty-string $encoded */
        $encoded = json_encode($data, JSON_THROW_ON_ERROR);

        return $encoded;
    }
}
