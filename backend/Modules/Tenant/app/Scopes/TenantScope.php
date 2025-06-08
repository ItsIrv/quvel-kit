<?php

namespace Modules\Tenant\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Traits\GetsTenant;

class TenantScope implements Scope
{
    use GetsTenant;

    /**
     * Apply the scope to a given Eloquent query.
     *
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Check if tenant resolution has been bypassed for this request
        $tenantContext = app(TenantContext::class);

        if ($tenantContext->isBypassed()) {
            return;
        }

        $builder->where(
            'tenant_id',
            '=',
            $this->getTenantId(),
        );
    }
}
