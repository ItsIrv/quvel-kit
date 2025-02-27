<?php

namespace Modules\Tenant\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\app\Traits\TenantScopedModel;

class TestTenantModel extends Model
{
    use HasFactory;
    use TenantScopedModel;

    protected $table = 'test_tenant_models';
    protected $fillable = ['name', 'tenant_id'];
    public $timestamps = true;
}
