<?php

namespace Modules\Tenant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonException;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * @implements CastsAttributes<TenantConfig, string|null>
 */
class TenantConfigCast implements CastsAttributes
{
    /**
     * Cast the stored value into a `TenantConfig` object.
     *
     * @throws JsonException
     */
    public function get($model, string $key, mixed $value, array $attributes): ?TenantConfig
    {
        if (empty($value)) {
            return null;
        }

        return TenantConfig::fromArray(json_decode($value, true, 512, JSON_THROW_ON_ERROR) ?? []);
    }

    /**
     * Cast the `TenantConfig` object back into JSON for storage.
     *
     * @param  TenantConfig|array<string, mixed>|null  $value
     *
     * @throws JsonException
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        /** @var non-empty-string $encoded */
        $encoded = json_encode(
            $value instanceof TenantConfig ? $value->toArray() : (array) $value,
            JSON_THROW_ON_ERROR,
        );

        return $encoded;
    }
}
