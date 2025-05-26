<?php

namespace Modules\Tenant\Tests\Unit\Transformers;

use Illuminate\Http\Request;
use Modules\Tenant\database\factories\DynamicTenantConfigFactory;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantDumpResource::class)]
#[Group('tenant-module')]
#[Group('tenant-transformers')]
class TenantDumpResourceTest extends TestCase
{
    /**
     * Test that the transformer converts a tenant model to an array correctly with no parent or config.
     */
    public function testToArrayTransformsTenantWithoutParentOrConfig(): void
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

        $transformer = new TenantDumpResource($tenant);
        $result      = $transformer->toArray(new Request());

        // The config should now have data from providers even without tenant config
        $this->assertEquals('public-id-1', $result['id']);
        $this->assertEquals('Tenant Name', $result['name']);
        $this->assertEquals('tenant.com', $result['domain']);
        $this->assertEquals($tenant->created_at, $result['created_at']);
        $this->assertEquals($tenant->updated_at, $result['updated_at']);
        $this->assertNull($result['parent_id']);

        // Config should include provider data
        $this->assertIsArray($result['config']);
        $this->assertArrayHasKey('tenantId', $result['config']);
        $this->assertArrayHasKey('tenantName', $result['config']);
        $this->assertArrayHasKey('__visibility', $result['config']);
        $this->assertEquals('public-id-1', $result['config']['tenantId']);
        $this->assertEquals('Tenant Name', $result['config']['tenantName']);
    }

    /**
     * Test that the transformer includes the parent_id when the tenant has a parent.
     */
    public function testToArrayIncludesParentIdWhenTenantHasParent(): void
    {
        // Create a parent tenant
        $parentTenant = Tenant::factory()->create();

        // Create a child tenant
        $childTenant = Tenant::factory()->create([
            'parent_id' => $parentTenant->id,
        ]);

        $transformer = new TenantDumpResource($childTenant);
        $result      = $transformer->toArray(new Request());

        $this->assertEquals($parentTenant->public_id, $result['parent_id']);
    }

    /**
     * Test that the transformer filters config correctly using the correct factory.
     */
    public function testToArrayFiltersTenantConfigCorrectly(): void
    {
        $tenantConfig = DynamicTenantConfigFactory::createStandardTier(
            domain: 'api.example.com',
            appName: 'Example App',
            mailFromName: 'Example Support',
            mailFromAddress: 'support@example.com',
        );

        $tenant = Tenant::factory()->make(['config' => $tenantConfig]);

        $transformer = new TenantDumpResource($tenant);
        $result      = $transformer->toArray(new Request());

        // Check that private fields are not included
        $this->assertArrayNotHasKey('mail_from_name', $result['config']);
        $this->assertArrayNotHasKey('mail_from_address', $result['config']);
        $this->assertArrayNotHasKey('db_password', $result['config']);
        $this->assertArrayNotHasKey('cache_prefix', $result['config']);

        // Check that public/protected fields are included (transformed by providers)
        $this->assertArrayHasKey('appName', $result['config']);
        $this->assertArrayHasKey('apiUrl', $result['config']);
        $this->assertArrayHasKey('appUrl', $result['config']);
        $this->assertArrayHasKey('tenantId', $result['config']);
        $this->assertArrayHasKey('tenantName', $result['config']);
        $this->assertArrayHasKey('__visibility', $result['config']);

        // Visibility array should be present
        $this->assertIsArray($result['config']['__visibility']);

        // Verify some values
        $this->assertEquals('Example App', $result['config']['appName']);
        $this->assertEquals($tenant->public_id, $result['config']['tenantId']);
        $this->assertEquals($tenant->name, $result['config']['tenantName']);
    }

    /**
     * Test that the transformer handles missing config properly.
     */
    public function testToArrayHandlesMissingConfigGracefully(): void
    {
        $tenant = Tenant::factory()->make(['config' => null]);

        $transformer = new TenantDumpResource($tenant);
        $result      = $transformer->toArray(new Request());

        // Even without tenant config, providers should add data
        $this->assertIsArray($result['config']);
        $this->assertArrayHasKey('tenantId', $result['config']);
        $this->assertArrayHasKey('tenantName', $result['config']);
        $this->assertArrayHasKey('__visibility', $result['config']);
        $this->assertEquals($tenant->public_id, $result['config']['tenantId']);
        $this->assertEquals($tenant->name, $result['config']['tenantName']);
    }
}
