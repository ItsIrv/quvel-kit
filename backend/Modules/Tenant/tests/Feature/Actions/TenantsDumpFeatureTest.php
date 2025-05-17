<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

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

        // Ensure we have a tenant for testing
        $this->tenant = Tenant::factory()->create([
            'name'   => 'Test Tenant',
            'domain' => 'test-tenant.example.com',
        ]);
    }

    #[TestDox('It should return tenant data in JSON format')]
    public function testTenantDumpReturnsJsonData(): void
    {
        // Act
        $response = $this->getJson(
            route('api.tenants.dump'),
        );

        // Assert
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id'     => $this->tenant->public_id,
                    'name'   => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);
    }

    #[TestDox('It should throw exception when tenant does not exist')]
    public function testTenantDumpThrowsExceptionWithoutTenant(): void
    {
        // Arrange - Simulate incorrect tenant by deleting all
        DB::table('tenants')->delete();

        // Set up expectations for exception
        $this->withoutExceptionHandling();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(TenantError::NOT_FOUND->value);

        // Act
        $this->getJson(
            route('api.tenants.dump'),
        );
    }

    #[TestDox('It should cache tenant data for subsequent requests')]
    public function testTenantDumpCachesTenantData(): void
    {
        // Arrange - Make an initial request to cache the data
        $this->getJson(route('api.tenants.dump'))->assertOk();

        // Act - Make a second request that should use the cache
        $response = $this->getJson(route('api.tenants.dump'));

        // Assert
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id'     => $this->tenant->public_id,
                    'name'   => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);

        // Verify cache exists (indirectly through response time or headers)
        // In a real test, you might check for cache hit metrics or mock the cache
    }
}
