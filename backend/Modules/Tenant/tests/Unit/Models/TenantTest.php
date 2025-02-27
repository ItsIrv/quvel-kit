<?php

namespace Modules\Tenant\Tests\Unit\Models;

use Modules\Tenant\App\Models\Tenant;
use Modules\Tenant\Database\Factories\TenantFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(Tenant::class)]
#[Group('tenant-module')]
#[Group('tenant-models')]

class TenantTest extends TestCase
{
    /**
     * Test that the Tenant model has the expected fillable properties.
     */
    public function testHasFillableProperties(): void
    {
        $tenant = new Tenant();

        $this->assertEquals(
            ['name', 'domain'],
            $tenant->getFillable(),
        );
    }

    /**
     * Test that the Tenant model can create a new factory instance.
     */
    public function testCanCreateNewFactoryInstance(): void
    {
        $factory = Tenant::newFactory();

        $this->assertInstanceOf(
            TenantFactory::class,
            $factory,
        );
    }
}
