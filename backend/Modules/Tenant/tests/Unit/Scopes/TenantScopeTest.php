<?php

namespace Modules\Tenant\Tests\Unit\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Scopes\TenantScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantScope::class)]
#[Group('tenant-module')]
#[Group('tenant-scopes')]
class TenantScopeTest extends TestCase
{
    /**
     * Test that the scope is applied correctly.
     */
    public function test_apply_scope(): void
    {
        $mockBuilder = $this->createMock(Builder::class);
        $mockBuilder->expects($this->once())
            ->method('where')
            ->with('tenant_id', '=', $this->tenant->id);

        $mockModel = $this->createMock(Model::class);

        $scope = new TenantScope();
        $scope->apply($mockBuilder, $mockModel);
    }
}
