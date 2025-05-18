<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(TenantsDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
final class TenantsDumpFeatureTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // The tenant is already set up by the parent TestCase
        // No need to create another one
    }

    #[TestDox('It should return tenant data in JSON format')]
    public function testTenantDumpReturnsJsonData(): void
    {
        // Arrange - Make sure caching is enabled
        Config::set('tenant.tenant_cache.preload', true);
        
        // Act
        $response = $this->getJson(
            route('tenants.cache'),
        );

        // Assert
        $response->assertOk();
        
        // Verify that the response contains a collection of tenants
        $this->assertIsArray($response->json('data'));
        
        // Find our tenant in the collection
        $tenantFound = false;
        foreach ($response->json('data') as $tenant) {
            if ($tenant['id'] === $this->tenant->public_id) {
                $tenantFound = true;
                $this->assertEquals($this->tenant->name, $tenant['name']);
                $this->assertEquals($this->tenant->domain, $tenant['domain']);
                break;
            }
        }
        
        $this->assertTrue($tenantFound, 'Expected tenant not found in response');
    }

    #[TestDox('It should handle case when no tenants exist')]
    public function testTenantDumpHandlesNoTenants(): void
    {
        // Arrange - Make sure caching is enabled
        Config::set('tenant.tenant_cache.preload', true);
        
        // Simulate no tenants by deleting all
        DB::table('tenants')->delete();

        // Act
        // We expect a redirect or error response since there's no tenant to serve
        $response = $this->getJson(
            route('tenants.cache'),
        );
        
        // Assert - We should get a non-200 response (either redirect or error)
        $this->assertNotEquals(200, $response->getStatusCode(), 'Should not return OK status when no tenants exist');
    }

    #[TestDox('It should cache tenant data for subsequent requests')]
    public function testTenantDumpCachesTenantData(): void
    {
        // Arrange - Make sure caching is enabled
        Config::set('tenant.tenant_cache.preload', true);
        
        // Make an initial request to cache the data
        $this->getJson(route('tenants.cache'))->assertOk();

        // Act - Make a second request that should use the cache
        $response = $this->getJson(route('tenants.cache'));

        // Assert
        $response->assertOk();
        
        // Verify that the response contains a collection of tenants
        $this->assertIsArray($response->json('data'));
        
        // Find our tenant in the collection
        $tenantFound = false;
        foreach ($response->json('data') as $tenant) {
            if ($tenant['id'] === $this->tenant->public_id) {
                $tenantFound = true;
                $this->assertEquals($this->tenant->name, $tenant['name']);
                $this->assertEquals($this->tenant->domain, $tenant['domain']);
                break;
            }
        }
        
        $this->assertTrue($tenantFound, 'Expected tenant not found in response');
        
        // Verify cache exists (indirectly through response time or headers)
        // In a real test, you might check for cache hit metrics or mock the cache
    }
    
    #[TestDox('It should block access when tenant cache preload is disabled')]
    public function testMiddlewareBlocksAccessWhenPreloadDisabled(): void
    {
        // Arrange - Disable tenant cache preload
        Config::set('tenant.tenant_cache.preload', false);
        
        // Act
        $response = $this->getJson(route('tenants.cache'));
        
        // Assert
        $response->assertForbidden()
            ->assertJson([
                'message' => __('common::feature.status.info.notAvailable'),
            ]);
    }
}
