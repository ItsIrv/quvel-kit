<?php

namespace Modules\Tenant\Tests\Unit\Transformers;

use Illuminate\Http\Request;
use Modules\Tenant\Transformers\TenantDumpTransformer;
use Modules\Tenant\App\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantDumpTransformer::class)]
#[Group('tenant-module')]
class TenantDumpTransformerTest extends TestCase
{
    /**
     * Test that the transformer converts a tenant model to an array correctly.
     */
    public function testToArrayTransformsTenantCorrectly(): void
    {
        $tenant = Tenant::factory()->make();
        $tenant->setRawAttributes([
            'id'         => 1,
            'public_id'  => 'public-id-1',
            'name'       => 'Tenant Name',
            'domain'     => 'tenant.com',
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
        ], true);

        $transformer = new TenantDumpTransformer($tenant);
        $result      = $transformer->toArray(
            new Request(),
        );

        $this->assertEquals([
            'id'         => 'public-id-1',
            'name'       => 'Tenant Name',
            'domain'     => 'tenant.com',
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
        ], $result);
    }
}
