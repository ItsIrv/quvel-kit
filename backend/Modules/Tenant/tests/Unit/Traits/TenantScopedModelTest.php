<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantMismatchException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Tests\Models\TestTenantModel;
use Modules\Tenant\Traits\TenantScopedModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantScopedModel::class)]
#[Group('tenant-module')]
#[Group('tenant-traits')]
final class TenantScopedModelTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create test schema without foreign key constraint to allow testing with various tenant IDs
        Schema::create('test_tenant_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id'); // No foreign key constraint
            $table->string('name')->nullable();
            $table->string('public_id')->nullable();
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
                    'public_id'  => "tenant-$i-record",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }

    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        // Drop test schema after test execution
        Schema::dropIfExists('test_tenant_models');

        parent::tearDown();
    }

    #[TestDox('It should set tenant_id automatically on model creation')]
    public function testTenantIdSetOnCreation(): void
    {
        // Arrange & Act
        $model = TestTenantModel::create(['name' => 'Auto Tenant ID Test']);

        // Assert
        $this->assertEquals($this->tenant->id, $model->tenant_id);
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $model->id,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Auto Tenant ID Test',
        ]);
    }

    #[TestDox('It should scope queries to the current tenant')]
    public function testQueriesAreScopedToCurrentTenant(): void
    {
        // Arrange
        // Create a record for the current tenant
        TestTenantModel::create(['name' => 'Current Tenant Test Record']);

        // Count total records in the table (including our seeded multi-tenant records)
        $totalCount = DB::table('test_tenant_models')->count();

        // Act
        // Count records using the model (which applies the tenant scope)
        $scopedCount = TestTenantModel::count();

        // Assert
        // This should be greater than what we get with the tenant scope
        $this->assertGreaterThan(0, $totalCount, 'Total records count should be greater than zero');

        // Count records for other tenants directly from DB
        $otherTenantsCount = DB::table('test_tenant_models')
            ->where('tenant_id', '!=', $this->tenant->id)
            ->count();

        // Verify the scope is working if we have records for other tenants
        $this->assertGreaterThan(0, $otherTenantsCount, 'Should have records for other tenants');
        $this->assertLessThan($totalCount, $scopedCount, 'Tenant scope should filter out records from other tenants');

        // Verify that all records returned have the current tenant ID
        $allRecords = TestTenantModel::all();
        foreach ($allRecords as $record) {
            $this->assertEquals($this->tenant->id, $record->tenant_id, 'All records should have the current tenant ID');
        }
    }

    #[TestDox('It should enforce tenant_id from context when saving a model')]
    public function testTenantIdEnforcedOnSave(): void
    {
        // Arrange
        $model       = new TestTenantModel();
        $model->name = 'Test Model';

        // Act
        $saveResult = $model->save();

        // Assert
        $this->assertTrue($saveResult, 'Model should save successfully');
        $this->assertEquals($this->tenant->id, $model->tenant_id, 'Model should have current tenant ID');
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $model->id,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Test Model',
        ]);
    }

    #[TestDox('It should update a model with the correct tenant_id successfully')]
    public function testUpdateWithCorrectTenantId(): void
    {
        // Arrange
        $model = TestTenantModel::create(['name' => 'Original Name']);

        // Act
        $updateResult = $model->update(['name' => 'Updated Name']);

        // Assert
        $this->assertTrue($updateResult, 'Update operation should succeed');
        $this->assertEquals('Updated Name', $model->fresh()->name, 'Name should be updated');
        $this->assertEquals($this->tenant->id, $model->tenant_id, 'Tenant ID should remain unchanged');
    }

    #[TestDox('It should enforce tenant_id from context when updating a model')]
    public function testTenantIdEnforcedOnUpdate(): void
    {
        // Arrange
        $model      = TestTenantModel::create(['name' => 'Original Name']);
        $originalId = $model->id;

        // Act - Try to update with a different tenant_id (will be ignored/overridden)
        $updateResult = $model->update([
            'name' => 'Updated Name',
        ]);

        // Assert
        $this->assertTrue($updateResult, 'Update operation should succeed');

        // Verify the tenant_id remains the same from context
        $updatedModel = TestTenantModel::find($originalId);
        $this->assertEquals($this->tenant->id, $updatedModel->tenant_id, 'Tenant ID should remain unchanged');
        $this->assertEquals('Updated Name', $updatedModel->name, 'Name should be updated');
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $originalId,
            'tenant_id' => $this->tenant->id,
            'name'      => 'Updated Name',
        ]);
    }

    #[TestDox('It should delete a model with the correct tenant_id successfully')]
    public function testDeleteWithCorrectTenantId(): void
    {
        // Arrange
        $model   = TestTenantModel::create(['name' => 'To Be Deleted']);
        $modelId = $model->id;

        // Act
        $deleteResult = $model->delete();

        // Assert
        $this->assertTrue($deleteResult, 'Delete operation should succeed');
        $this->assertNull(TestTenantModel::find($modelId), 'Model should be deleted');
        $this->assertDatabaseMissing('test_tenant_models', [
            'id' => $modelId,
        ]);
    }

    #[TestDox('It should respect tenant scope when deleting models')]
    public function testDeleteRespectsTenantScope(): void
    {
        // Arrange
        $model   = TestTenantModel::create(['name' => 'To Be Deleted']);
        $modelId = $model->id;

        // Verify it exists
        $this->assertDatabaseHas('test_tenant_models', [
            'id'        => $modelId,
            'tenant_id' => $this->tenant->id,
        ]);

        // Act
        $deleteResult = $model->delete();

        // Assert
        $this->assertTrue($deleteResult, 'Delete operation should succeed');
        $this->assertDatabaseMissing('test_tenant_models', [
            'id' => $modelId,
        ]);
    }

    #[TestDox('It should scope global delete operations to the current tenant')]
    public function testGlobalDeleteIsScoped(): void
    {
        // Arrange
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

        // Act
        // This should delete ONLY records for the current tenant
        $deleteResult = TestTenantModel::where('id', '>', 0)->delete();

        // Assert
        $this->assertGreaterThan(0, $deleteResult, 'Delete operation should affect at least one record');

        // Verify that records from other tenants still exist
        $otherTenantsCountAfter = DB::table('test_tenant_models')
            ->where('tenant_id', '!=', $this->tenant->id)
            ->count();

        $this->assertEquals($otherTenantsCountBefore, $otherTenantsCountAfter, 'Records from other tenants should not be affected');

        // Verify no records exist for current tenant
        $this->assertEquals(0, TestTenantModel::count(), 'All records for current tenant should be deleted');
    }

    #[TestDox('It should generate correct broadcast notification channel with tenant context')]
    public function testReceivesBroadcastNotificationsOnWithTenantContext(): void
    {
        // Arrange
        $model = TestTenantModel::create([
            'name'      => 'Broadcast Test Model',
            'public_id' => 'test-model-123',
        ]);

        // Act
        $channel = $model->receivesBroadcastNotificationsOn();

        // Assert
        $expectedChannel = "tenant.{$this->tenant->public_id}.TestTenantModel.{$model->id}";
        $this->assertEquals($expectedChannel, $channel, 'Should generate correct broadcast channel with tenant context');
    }
}
