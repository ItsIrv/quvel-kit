<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(FindService::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
class TenantFindServiceTest extends TestCase
{
    private FindService $service;

    #[Before]
    public function setUpTest(): void
    {
        $this->service = new FindService();
    }

    /**
     * Test that the findTenantByDomain method returns the correct tenant.
     */
    public function testFindTenantByDomain(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'example.com',
        ]);

        $foundTenant = $this->service->findTenantByDomain('example.com');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
    }

    /**
     * Test that the findTenantByDomain method returns null for a non-existent domain.
     */
    public function testFindTenantByDomainReturnsNull(): void
    {
        $foundTenant = $this->service->findTenantByDomain('nonexistent.com');

        $this->assertNull($foundTenant);
    }

    /**
     * Test that the findTenantByDomain method returns the parent tenant if it exists.
     */
    public function testFindTenantByDomainReturnsParent(): void
    {
        $parentTenant = Tenant::factory()->create([
            'domain' => 'parent.com',
        ]);

        Tenant::factory()->create([
            'domain'    => 'child.com',
            'parent_id' => $parentTenant->id,
        ]);

        $foundTenant = $this->service->findTenantByDomain('child.com');

        $this->assertNotNull($foundTenant);
        $this->assertEquals($parentTenant->id, $foundTenant->id);
    }

    /**
     * Test that findAll method returns all tenants.
     */
    public function testFindAllReturnsAllTenants(): void
    {
        Tenant::truncate();

        // Create multiple tenants
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Call the findAll method
        $tenants = $this->service->findAll();

        // Assert that the returned collection contains all created tenants
        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->contains($tenant1));
        $this->assertTrue($tenants->contains($tenant2));
    }
}
