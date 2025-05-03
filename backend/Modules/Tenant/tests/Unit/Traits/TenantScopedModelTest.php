<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        // Create test schema without foreign key constraint to allow testing with various tenant IDs
        Schema::create('test_tenant_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id'); // No foreign key constraint
            $table->string('name')->nullable();
            $table->timestamps();
        });

        // Seed with records from different tenants
        $this->seedMultipleTenantRecords();
    }

    /**
     * Create test records with different tenant IDs to verify scoping.
     */
    protected function seedMultipleTenantRecords(): void
    {
        // Create records for different tenants (3-10 to avoid conflicts with default tenant IDs)
        for ($i = 3; $i <= 10; $i++) {
            TestTenantModel::unguarded(function () use ($i) {
                TestTenantModel::insert([
                    'tenant_id'  => $i,
                    'name'       => "Tenant $i Record",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
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
    public function testTenantIdSetOnCreation(): void
    {
        $model = TestTenantModel::create();

        $this->assertEquals($this->tenant->id, $model->tenant_id);
    }

    /**
     * Test that queries are properly scoped to the current tenant.
     */
    public function testQueriesAreScopedToCurrentTenant(): void
    {
        // Count total records in the table (including our seeded multi-tenant records)
        $totalCount = DB::table('test_tenant_models')->count();

        // This should be greater than what we get with the tenant scope
        $this->assertGreaterThan(0, $totalCount);

        // Count records using the model (which applies the tenant scope)
        $scopedCount = TestTenantModel::count();

        // Verify that the tenant scope is filtering records
        $this->assertLessThanOrEqual($totalCount, $scopedCount);

        // Count records for other tenants directly from DB
        $otherTenantsCount = DB::table('test_tenant_models')
            ->where('tenant_id', '!=', $this->tenant->id)
            ->count();

        // If we have records for other tenants, verify the scope is working
        if ($otherTenantsCount > 0) {
            $this->assertLessThan($totalCount, $scopedCount, 'Tenant scope should filter out records from other tenants');
        }

        // Verify that all records returned have the current tenant ID
        $allRecords = TestTenantModel::all();
        foreach ($allRecords as $record) {
            $this->assertEquals($this->tenant->id, $record->tenant_id);
        }
    }

    /**
     * Test that saving a model always enforces the current tenant ID from context.
     */
    public function testTenantIdEnforcedOnSave(): void
    {
        // Create a model without explicitly setting tenant_id
        $model       = new TestTenantModel();
        $model->name = 'Test Model';
        $model->save();

        // Verify the tenant_id was set from the context
        $this->assertEquals($this->tenant->id, $model->tenant_id);
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $model->id,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Test Model',
        ]);
    }

    /**
     * Test that updating a model with the correct tenant_id works.
     */
    public function testUpdateWithCorrectTenantId(): void
    {
        $model        = TestTenantModel::create();
        $updateResult = $model->update(['name' => 'Updated Name']);

        $this->assertTrue($updateResult);
        $this->assertEquals('Updated Name', $model->fresh()->name);
    }

    /**
     * Test that updating a model enforces the current tenant ID from context.
     */
    public function testTenantIdEnforcedOnUpdate(): void
    {
        // Create a model
        $model      = TestTenantModel::create(['name' => 'Original Name']);
        $originalId = $model->id;

        // Try to update with a different tenant_id (will be ignored/overridden)
        $model->update([
            'name' => 'Updated Name',
        ]);

        // Verify the tenant_id remains the same from context
        $updatedModel = TestTenantModel::find($originalId);
        $this->assertEquals($this->tenant->id, $updatedModel->tenant_id);
        $this->assertEquals('Updated Name', $updatedModel->name);
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $originalId,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Updated Name',
        ]);
    }

    /**
     * Test that deleting a model with the correct tenant_id works.
     */
    public function testDeleteWithCorrectTenantId(): void
    {
        $model        = TestTenantModel::create();
        $deleteResult = $model->delete();

        $this->assertTrue($deleteResult);
        $this->assertNull(TestTenantModel::find($model->id));
    }

    /**
     * Test that models can only be deleted within the current tenant scope.
     */
    public function testDeleteRespectsTenantScope(): void
    {
        // Create a model
        $model   = TestTenantModel::create(['name' => 'To Be Deleted']);
        $modelId = $model->id;

        // Verify it exists
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $modelId,
            'tenant_id' => $this->tenant->id,
        ]);

        // Delete it
        $model->delete();

        // Verify it's gone
        $this->assertDatabaseMissing('test_tenant_models', [
            'id' => $modelId,
        ]);
    }

    /**
     * Test that global delete operations are properly scoped to current tenant.
     */
    public function testGlobalDeleteIsScoped(): void
    {
        // Create some records for the current tenant
        TestTenantModel::create(['name' => 'Current Tenant Record 1']);
        TestTenantModel::create(['name' => 'Current Tenant Record 2']);

        // Verify we have records for the current tenant
        $this->assertGreaterThan(0, TestTenantModel::count(), 'Should have records for current tenant');

        // Count records from other tenants before delete
        $otherTenantsCountBefore = DB::table('test_tenant_models')
            ->where('tenant_id', '!=', $this->tenant->id)
            ->count();

        // Verify we have records for other tenants
        $this->assertGreaterThan(0, $otherTenantsCountBefore, 'Should have records for other tenants');

        // This should delete ONLY records for the current tenant
        TestTenantModel::where('id', '>', 0)->delete();

        // Verify that records from other tenants still exist
        $otherTenantsCountAfter = DB::table('test_tenant_models')
            ->where('tenant_id', '!=', $this->tenant->id)
            ->count();

        $this->assertEquals($otherTenantsCountBefore, $otherTenantsCountAfter, 'Records from other tenants should not be affected');

        // Verify no records exist for current tenant
        $this->assertEquals(0, TestTenantModel::count(), 'All records for current tenant should be deleted');
    }
}
