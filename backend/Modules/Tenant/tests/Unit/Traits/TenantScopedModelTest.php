<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Exceptions\TenantMismatchException;
use Modules\Tenant\Tests\Models\TestTenantModel;
use Modules\Tenant\Traits\TenantScopedModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantScopedModel::class)]
#[Group('tenant-module')]
#[Group('tenant-traits')]
class TenantScopedModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create test schema
        Schema::create('test_tenant_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        // Drop test schema after test execution
        Schema::dropIfExists('test_tenant_models');

        parent::tearDown();
    }

    /**
     * Test that a model with the TenantScopedModel trait automatically sets the tenant_id on creation.
     */
    public function test_tenant_id_set_on_creation(): void
    {
        $model = TestTenantModel::create();

        $this->assertEquals($this->tenant->id, $model->tenant_id);
    }

    /**
     * Test that saving a model with a different tenant_id throws a TenantMismatchException.
     */
    public function test_save_throws_exception_for_different_tenant_id(): void
    {
        $this->expectException(TenantMismatchException::class);

        $model = new TestTenantModel;
        $model->tenant_id = $this->tenant->id + 1; // Different tenant ID
        $model->save();
    }

    /**
     * Test that updating a model with the correct tenant_id works.
     */
    public function test_update_with_correct_tenant_id(): void
    {
        $model = TestTenantModel::create();
        $updateResult = $model->update(['name' => 'Updated Name']);

        $this->assertTrue($updateResult);
        $this->assertEquals('Updated Name', $model->fresh()->name);
    }

    /**
     * Test that updating a model with a different tenant_id throws TenantMismatchException.
     */
    public function test_update_throws_exception_for_different_tenant_id(): void
    {
        $this->expectException(TenantMismatchException::class);

        $model = TestTenantModel::create();
        $model->tenant_id = $this->tenant->id + 1;
        $model->update(['name' => 'Should Fail']);
    }

    /**
     * Test that deleting a model with the correct tenant_id works.
     */
    public function test_delete_with_correct_tenant_id(): void
    {
        $model = TestTenantModel::create();
        $deleteResult = $model->delete();

        $this->assertTrue($deleteResult);
        $this->assertNull(TestTenantModel::find($model->id));
    }

    /**
     * Test that deleting a model with a different tenant_id throws TenantMismatchException.
     */
    public function test_delete_throws_exception_for_different_tenant_id(): void
    {
        $this->expectException(TenantMismatchException::class);

        $model = TestTenantModel::create();
        $model->tenant_id = $this->tenant->id + 1;
        $model->delete();
    }
}
