<?php

namespace Modules\Tenant\Tests\Unit\Transformers;

use Illuminate\Http\Request;
use Modules\Tenant\database\factories\TenantConfigFactory;
use Modules\Tenant\Http\Middleware\TenantDumpResource;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantDumpResource::class)]
#[Group('tenant-module')]
#[Group('tenant-transformers')]
class TenantDumpTransformerTest extends TestCase
{
    /**
     * Test that the transformer converts a tenant model to an array correctly with no parent or config.
     */
    public function test_to_array_transforms_tenant_without_parent_or_config(): void
    {
        $tenant = Tenant::factory()->make();
        $tenant->setRawAttributes([
            'id' => 1,
            'public_id' => 'public-id-1',
            'name' => 'Tenant Name',
            'domain' => 'tenant.com',
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
        ], true);

        $transformer = new TenantDumpResource($tenant);
        $result = $transformer->toArray(new Request);

        $this->assertEquals([
            'id' => 'public-id-1',
            'name' => 'Tenant Name',
            'domain' => 'tenant.com',
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
            'config' => [],
            'parent_id' => null,
        ], $result);
    }

    /**
     * Test that the transformer includes the parent_id when the tenant has a parent.
     */
    public function test_to_array_includes_parent_id_when_tenant_has_parent(): void
    {
        $transformer = new TenantDumpResource($this->tenant->children()->first());
        $result = $transformer->toArray(new Request);

        $this->assertEquals($this->tenant->public_id, $result['parent_id']);
    }

    /**
     * Test that the transformer filters config correctly using the correct factory.
     */
    public function test_to_array_filters_tenant_config_correctly(): void
    {
        $tenantConfigArray = TenantConfigFactory::create(
            apiDomain: 'api.example.com',
            appName: 'Example App',
            appEnv: 'production',
            mailFromName: 'Example Support',
            mailFromAddress: 'support@example.com',
        );

        $tenant = Tenant::factory()->make(['config' => $tenantConfigArray]);

        $transformer = new TenantDumpResource($tenant);
        $result = $transformer->toArray(new Request);

        $this->assertArrayNotHasKey('mail_from_name', $result['config']);
        $this->assertArrayNotHasKey('mail_from_address', $result['config']);
        $this->assertArrayNotHasKey('app_env', $result['config']);
        $this->assertArrayNotHasKey('debug', $result['config']);
        $this->assertEquals($tenantConfigArray['app_name'], $result['config']['app_name']);
        $this->assertEquals($tenantConfigArray['app_url'], $result['config']['app_url']);
        $this->assertEquals($tenantConfigArray['api_url'], $result['config']['api_url']);
        $this->assertEquals(
            $tenantConfigArray['__visibility'],
            $result['config']['__visibility'],
        );
    }

    /**
     * Test that the transformer handles missing config properly.
     */
    public function test_to_array_handles_missing_config_gracefully(): void
    {
        $tenant = Tenant::factory()->make(['config' => null]);

        $transformer = new TenantDumpResource($tenant);
        $result = $transformer->toArray(new Request);

        $this->assertEquals([], $result['config']);
    }
}
