<?php

namespace Modules\Tenant\app\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Tenant\app\Traits\GetsTenant;

class TenantScope implements Scope
{
    use GetsTenant;

    /**
     * Apply the scope to a given Eloquent query.
     *
     * @param  Builder<Model>  $builder
     * @param  Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('tenant_id', '=', $this->getTenant()->id);
    }
}
