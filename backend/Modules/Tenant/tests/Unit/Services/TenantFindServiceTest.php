<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\App\Models\Tenant;
use Modules\Tenant\App\Services\TenantFindService;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantFindService::class)]
#[Group('tenant-module')]
#[Group('services')]
class TenantFindServiceTest extends TestCase
{
    private TenantFindService $service;

    #[Before]
    public function setUpTest(): void
    {
        $this->service = new TenantFindService();
    }

    /**
     * Test that the findTenantByDomain method returns the correct tenant.
     */
    public function testFindTenantByDomain(): void
    {
        $tenant      = Tenant::factory()->create(
            [
                'domain' => 'example.com',
            ],
        );
        $foundTenant = $this->service->findTenantByDomain(
            'example.com',
        );

        $this->assertNotNull($foundTenant);
        $this->assertEquals(
            $tenant->id,
            $foundTenant->id,
        );
    }

    /**
     * Test that the findTenantByDomain method returns null for a non-existent domain.
     */
    public function testFindTenantByDomainReturnsNull(): void
    {
        $foundTenant = $this->service->findTenantByDomain(
            'nonexistent.com',
        );

        $this->assertNull($foundTenant);
    }
}
