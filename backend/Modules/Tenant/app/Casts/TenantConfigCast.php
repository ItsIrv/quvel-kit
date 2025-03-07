<?php

namespace Modules\Tenant\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Modules\Tenant\ValueObjects\TenantConfig;

class TenantConfigCast implements CastsAttributes
{
    /**
     * Cast the stored value into a `TenantConfig` object.
     */
    public function get($model, string $key, mixed $value, array $attributes): TenantConfig
    {
        return TenantConfig::fromArray(json_decode($value ?? '{}', true));
    }

    /**
     * Cast the `TenantConfig` object back into JSON for storage.
     */
    public function set($model, string $key, mixed $value, array $attributes): string
    {
        return json_encode($value instanceof TenantConfig ? $value->toArray() : $value);
    }
}
