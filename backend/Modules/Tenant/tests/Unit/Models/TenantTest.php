<?php

namespace Modules\Tenant\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Models\Tenant;
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
            ['name', 'domain', 'parent_id'],
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

    /**
     * Test that the parent relationship returns a BelongsTo instance.
     */
    public function testParentRelationship(): void
    {
        $tenant = new Tenant();

        $this->assertInstanceOf(BelongsTo::class, $tenant->parent());
    }

    /**
     * Test that the children relationship returns a HasMany instance.
     */
    public function testChildrenRelationship(): void
    {
        $tenant = new Tenant();

        $this->assertInstanceOf(HasMany::class, $tenant->children());
    }

    /**
     * Test that a tenant can be created.
     */
    public function testCreatingTenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name'   => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id'     => $tenant->id,
            'name'   => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
    }
}
