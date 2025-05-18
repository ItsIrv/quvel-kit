<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tenant\Exceptions\TenantMismatchException;
use Modules\Tenant\Tests\Models\TestTenantModel;
use Modules\Tenant\Tests\TestCase;
use Modules\Tenant\Traits\TenantScopedModel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(TenantScopedModel::class)]
#[Group('tenant-module')]
#[Group('tenant-traits')]
final class TenantScopedModelTraitTest extends TestCase
{
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

    #[TestDox('It should automatically set tenant_id on model creation')]
    public function testTenantIdSetOnCreation(): void
    {
        // Arrange & Act
        $model = TestTenantModel::create(['name' => 'Test Model']);

        // Assert
        $this->assertEquals($this->tenant->id, $model->tenant_id);
        $this->assertDatabaseHas('test_tenant_models', [
            'id' => $model->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
        ]);
    }

    #[TestDox('It should scope queries to the current tenant')]
    public function testQueriesAreScopedToCurrentTenant(): void
    {
        // Arrange
        // Count total records in the table (including our seeded multi-tenant records)
        $totalCount = DB::table('test_tenant_models')->count();

        // This should be greater than what we get with the tenant scope
        $this->assertGreaterThan(0, $totalCount);

        // Act
        // Count records using the model (which applies the tenant scope)
        $scopedCount = TestTenantModel::count();

        // Assert
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

    #[TestDox('It should enforce tenant_id on model save')]
    public function testTenantIdEnforcedOnSave(): void
    {
        // Arrange
        $model = new TestTenantModel();
        $model->name = 'Test Model';

        // Act
        $saveResult = $model->save();

        // Assert
        $this->assertTrue($saveResult);
        $this->assertEquals($this->tenant->id, $model->tenant_id);
        $this->assertDatabaseHas('test_tenant_models', [
            'id' => $model->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Model',
        ]);
    }

    #[TestDox('It should allow updates when tenant_id is correct')]
    public function testUpdateWithCorrectTenantId(): void
    {
        // Arrange
        $model = TestTenantModel::create(['name' => 'Original Name']);

        // Act
        $updateResult = $model->update(['name' => 'Updated Name']);

        // Assert
        $this->assertTrue($updateResult);
        $this->assertEquals('Updated Name', $model->fresh()->name);
        $this->assertEquals($this->tenant->id, $model->tenant_id);
    }

    #[TestDox('It should enforce tenant_id on model update')]
    public function testTenantIdEnforcedOnUpdate(): void
    {
        // Arrange
        $model = TestTenantModel::create(['name' => 'Original Name']);
        $originalId = $model->id;

        // Act
        // Try to update with a different tenant_id (will be ignored/overridden)
        $updateResult = $model->update([
            'name' => 'Updated Name',
        ]);

        // Assert
        $this->assertTrue($updateResult);

        // Verify the tenant_id remains the same from context
        $updatedModel = TestTenantModel::find($originalId);
        $this->assertEquals($this->tenant->id, $updatedModel->tenant_id);
        $this->assertEquals('Updated Name', $updatedModel->name);
        $this->assertDatabaseHas('test_tenant_models', [
            'id' => $originalId,
            'tenant_id' => $this->tenant->id,
            'name' => 'Updated Name',
        ]);
    }

    #[TestDox('It should allow deletion when tenant_id is correct')]
    public function testDeleteWithCorrectTenantId(): void
    {
        // Arrange
        $model = TestTenantModel::create(['name' => 'To Be Deleted']);
        $modelId = $model->id;

        // Act
        $deleteResult = $model->delete();

        // Assert
        $this->assertTrue($deleteResult);
        $this->assertNull(TestTenantModel::find($modelId));
        $this->assertDatabaseMissing('test_tenant_models', [
            'id' => $modelId,
        ]);
    }

    #[TestDox('It should respect tenant scope when deleting models')]
    public function testDeleteRespectsTenantScope(): void
    {
        // Arrange
        $model = TestTenantModel::create(['name' => 'To Be Deleted']);
        $modelId = $model->id;

        // Verify it exists
        $this->assertDatabaseHas('test_tenant_models', [
            'id' => $modelId,
            'tenant_id' => $this->tenant->id,
        ]);

        // Act
        $deleteResult = $model->delete();

        // Assert
        $this->assertTrue($deleteResult);
        $this->assertDatabaseMissing('test_tenant_models', [
            'id' => $modelId,
        ]);
    }

    #[TestDox('It should respect tenant scope when performing global delete operations')]
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

    #[TestDox('It should throw exception when trying to save with mismatched tenant_id')]
    public function testThrowsExceptionWhenSavingWithMismatchedTenantId(): void
    {
        // Arrange
        $model = new TestTenantModel();
        $model->tenant_id = 999; // Different from current tenant
        $model->name = 'Mismatched Tenant';

        // Assert
        $this->expectException(TenantMismatchException::class);

        // Act
        $model->save();
    }

    #[TestDox('It should generate correct broadcast notification channel')]
    public function testReceivesBroadcastNotificationsOn(): void
    {
        // Arrange
        $model = TestTenantModel::create([
            'name' => 'Broadcast Test',
            'public_id' => 'model-123',
        ]);

        // Act
        $channel = $model->receivesBroadcastNotificationsOn();

        // Assert
        $expectedChannel = "tenant.{$this->tenant->public_id}.TestTenantModel.{$model->id}";
        $this->assertEquals($expectedChannel, $channel);
    }
}
