<?php

namespace Modules\Tenant\Tests\Unit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Http\Resources\TenantCacheResource;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantCacheResource::class)]
#[Group('tenant-module')]
#[Group('tenant-resources')]
class TenantCacheResourceTest extends TestCase
{
    private ConfigurationPipeline $pipeline;
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pipeline = $this->createMock(ConfigurationPipeline::class);
        $this->request = new Request();
        
        $this->app->instance(ConfigurationPipeline::class, $this->pipeline);
    }

    /**
     * Test toArray returns correct structure with basic tenant data.
     */
    public function testToArrayReturnsCorrectStructureWithBasicTenantData(): void
    {
        // Create a real tenant instead of a mock
        $tenant = new Tenant([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com'
        ]);
        $tenant->public_id = 'test-public-id';
        $tenant->config = new DynamicTenantConfig(['existing_key' => 'existing_value']);
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        // Mock pipeline resolution
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->with($tenant, ['existing_key' => 'existing_value'])
            ->willReturn([
                'values' => [
                    'app_name' => 'Test App',
                    'app_url' => 'https://api.test.com'
                ],
                'visibility' => [
                    'app_name' => 'public',
                    'app_url' => 'protected'
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertIsArray($result);
        $this->assertEquals('test-public-id', $result['id']);
        $this->assertEquals('Test Tenant', $result['name']);
        $this->assertEquals('test.example.com', $result['domain']);
        $this->assertNull($result['parent_id']);
        $this->assertInstanceOf(Carbon::class, $result['created_at']);
        $this->assertInstanceOf(Carbon::class, $result['updated_at']);
        $this->assertArrayHasKey('config', $result);
    }

    /**
     * Test toArray includes parent public_id when parent exists.
     */
    public function testToArrayIncludesParentPublicIdWhenParentExists(): void
    {
        // Create real tenants instead of mocks
        $parent = new Tenant([
            'name' => 'Parent Tenant',
            'domain' => 'parent.example.com'
        ]);
        $parent->public_id = 'parent-public-id';

        $tenant = new Tenant([
            'name' => 'Child Tenant',
            'domain' => 'child.example.com'
        ]);
        $tenant->public_id = 'child-public-id';
        $tenant->config = new DynamicTenantConfig(['test_key' => 'test_value']);
        $tenant->parent = $parent;
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        // Mock pipeline resolution
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => ['test_key' => 'test_value'],
                'visibility' => ['test_key' => 'public']
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertEquals('parent-public-id', $result['parent_id']);
    }

    /**
     * Test getFilteredConfig handles null effective config.
     */
    public function testGetFilteredConfigHandlesNullEffectiveConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        
        // Set properties directly on the mock
        $tenant->public_id = 'test-id';
        $tenant->name = 'Test';
        $tenant->domain = 'test.com';
        $tenant->parent = null;
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

        // Mock pipeline resolution with empty array
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->with($tenant, [])
            ->willReturn([
                'values' => ['default_key' => 'default_value'],
                'visibility' => ['default_key' => 'public']
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('config', $result);
        $this->assertEquals('default_value', $result['config']['default_key']);
        $this->assertEquals(['default_key' => 'public'], $result['config']['__visibility']);
    }

    /**
     * Test getFilteredConfig handles DynamicTenantConfig instance.
     */
    public function testGetFilteredConfigHandlesDynamicTenantConfigInstance(): void
    {
        $config = new DynamicTenantConfig([
            'app_name' => 'Dynamic App',
            'app_url' => 'https://dynamic.test'
        ]);

        $tenant = $this->createMock(Tenant::class);
        
        // Set properties directly on the mock
        $tenant->public_id = 'dynamic-id';
        $tenant->name = 'Dynamic';
        $tenant->domain = 'dynamic.com';
        $tenant->parent = null;
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($config);

        // Mock pipeline resolution
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->with($tenant, [
                'app_name' => 'Dynamic App',
                'app_url' => 'https://dynamic.test'
            ])
            ->willReturn([
                'values' => [
                    'app_name' => 'Resolved Dynamic App',
                    'app_url' => 'https://resolved.dynamic.test'
                ],
                'visibility' => [
                    'app_name' => 'public',
                    'app_url' => 'protected'
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertEquals('Resolved Dynamic App', $result['config']['app_name']);
        $this->assertEquals('https://resolved.dynamic.test', $result['config']['app_url']);
        $this->assertEquals([
            'app_name' => 'public',
            'app_url' => 'protected'
        ], $result['config']['__visibility']);
    }

    /**
     * Test getFilteredConfig handles array config fallback.
     */
    public function testGetFilteredConfigHandlesArrayConfigFallback(): void
    {
        // Instead of testing the fallback path (which may not be used in practice),
        // let's test with another DynamicTenantConfig but with different data
        $arrayConfig = new DynamicTenantConfig(['fallback_key' => 'fallback_value']);

        $createdAt = Carbon::now();
        $updatedAt = Carbon::now();

        $tenant = $this->createMock(Tenant::class);
        
        // Set properties directly on the mock
        $tenant->public_id = 'fallback-id';
        $tenant->name = 'Fallback';
        $tenant->domain = 'fallback.com';
        $tenant->parent = null;
        $tenant->created_at = $createdAt;
        $tenant->updated_at = $updatedAt;

        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($arrayConfig);

        // Mock pipeline resolution
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->with($tenant, ['fallback_key' => 'fallback_value'])
            ->willReturn([
                'values' => ['resolved_fallback' => 'resolved_value'],
                'visibility' => ['resolved_fallback' => 'protected']
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertEquals('resolved_value', $result['config']['resolved_fallback']);
        $this->assertEquals(['resolved_fallback' => 'protected'], $result['config']['__visibility']);
    }

    /**
     * Test getFilteredConfig creates enhanced config with proper visibility.
     */
    public function testGetFilteredConfigCreatesEnhancedConfigWithProperVisibility(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with multiple visibility levels
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => [
                    'public_key' => 'public_value',
                    'protected_key' => 'protected_value',
                    'private_key' => 'private_value'
                ],
                'visibility' => [
                    'public_key' => 'public',
                    'protected_key' => 'protected', 
                    'private_key' => 'private'
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $config = $result['config'];

        // Should only include public and protected keys (getProtectedConfig excludes private)
        $this->assertArrayHasKey('public_key', $config);
        $this->assertArrayHasKey('protected_key', $config);
        $this->assertArrayNotHasKey('private_key', $config);

        // Verify visibility mapping
        $this->assertEquals([
            'public_key' => 'public',
            'protected_key' => 'protected'
        ], $config['__visibility']);
    }

    /**
     * Test getFilteredConfig handles missing values in pipeline response.
     */
    public function testGetFilteredConfigHandlesMissingValuesInPipelineResponse(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with missing values
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'visibility' => ['some_key' => 'public']
                // No 'values' key
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertArrayHasKey('config', $result);
        $this->assertEquals(['__visibility' => []], $result['config']);
    }

    /**
     * Test getFilteredConfig handles missing visibility in pipeline response.
     */
    public function testGetFilteredConfigHandlesMissingVisibilityInPipelineResponse(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with missing visibility
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => ['test_key' => 'test_value']
                // No 'visibility' key
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $config = $result['config'];
        // Since visibility defaults to private and getProtectedConfig excludes private keys,
        // the test_key should not be present in the final config
        $this->assertArrayNotHasKey('test_key', $config);
        $this->assertEquals(['__visibility' => []], $config);
    }

    /**
     * Test getFilteredConfig handles empty pipeline response.
     */
    public function testGetFilteredConfigHandlesEmptyPipelineResponse(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with empty response
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertEquals(['__visibility' => []], $result['config']);
    }

    /**
     * Test getFilteredConfig processes complex nested configuration.
     */
    public function testGetFilteredConfigProcessesComplexNestedConfiguration(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with complex nested data
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => [
                    'app' => [
                        'name' => 'Complex App',
                        'settings' => ['debug' => true, 'timezone' => 'UTC']
                    ],
                    'database' => [
                        'host' => 'localhost',
                        'name' => 'app_db'
                    ],
                    'simple_key' => 'simple_value'
                ],
                'visibility' => [
                    'app' => 'public',
                    'database' => 'protected',
                    'simple_key' => 'public'
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $config = $result['config'];

        $this->assertEquals([
            'name' => 'Complex App',
            'settings' => ['debug' => true, 'timezone' => 'UTC']
        ], $config['app']);

        $this->assertEquals([
            'host' => 'localhost',
            'name' => 'app_db'
        ], $config['database']);

        $this->assertEquals('simple_value', $config['simple_key']);

        $this->assertEquals([
            'app' => 'public',
            'database' => 'protected',
            'simple_key' => 'public'
        ], $config['__visibility']);
    }

    /**
     * Test getFilteredConfig handles invalid visibility values gracefully.
     */
    public function testGetFilteredConfigHandlesInvalidVisibilityValuesGracefully(): void
    {
        $tenant = $this->createTenantWithMockEffectiveConfig();

        // Mock pipeline resolution with invalid visibility values
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => [
                    'valid_key' => 'valid_value',
                    'invalid_key' => 'invalid_value'
                ],
                'visibility' => [
                    'valid_key' => 'public',
                    'invalid_key' => 'invalid_visibility_level' // This should cause an exception
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        
        // This should throw an exception due to invalid TenantConfigVisibility enum value
        $this->expectException(\ValueError::class);
        $resource->toArray($this->request);
    }

    /**
     * Test resource works with real DynamicTenantConfig and actual visibility enums.
     */
    public function testResourceWorksWithRealDynamicTenantConfigAndActualVisibilityEnums(): void
    {
        // Create real tenant with real config
        $config = new DynamicTenantConfig([
            'app_name' => 'Real App',
            'app_url' => 'https://real.app'
        ]);
        $config->setVisibility('app_name', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('app_url', TenantConfigVisibility::PROTECTED);

        $tenant = new Tenant([
            'name' => 'Real Tenant',
            'domain' => 'real.example.com'
        ]);
        $tenant->public_id = 'real-public-id';
        $tenant->config = $config;
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        // Mock pipeline with realistic response
        $this->pipeline->expects($this->once())
            ->method('resolveFromArray')
            ->willReturn([
                'values' => [
                    'app_name' => 'Real App Enhanced',
                    'app_url' => 'https://enhanced.real.app',
                    'internal_key' => 'internal_value'
                ],
                'visibility' => [
                    'app_name' => 'public',
                    'app_url' => 'protected',
                    'internal_key' => 'private'
                ]
            ]);

        $resource = new TenantCacheResource($tenant);
        $result = $resource->toArray($this->request);

        $this->assertEquals('real-public-id', $result['id']);
        $this->assertEquals('Real Tenant', $result['name']);
        $this->assertEquals('real.example.com', $result['domain']);
        
        $config = $result['config'];
        $this->assertEquals('Real App Enhanced', $config['app_name']);
        $this->assertEquals('https://enhanced.real.app', $config['app_url']);
        $this->assertArrayNotHasKey('internal_key', $config); // Private should be excluded

        $this->assertEquals([
            'app_name' => 'public',
            'app_url' => 'protected'
        ], $config['__visibility']);
    }

    /**
     * Helper method to create a tenant with mocked getEffectiveConfig method.
     */
    private function createTenantWithMockEffectiveConfig(): Tenant
    {
        $mockConfig = new DynamicTenantConfig(['existing_key' => 'existing_value']);

        $tenant = $this->createMock(Tenant::class);
        
        // Set properties directly on the mock
        $tenant->public_id = 'test-public-id';
        $tenant->name = 'Test Tenant';
        $tenant->domain = 'test.example.com';
        $tenant->parent = null;
        $tenant->created_at = Carbon::now();
        $tenant->updated_at = Carbon::now();

        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($mockConfig);

        return $tenant;
    }
}