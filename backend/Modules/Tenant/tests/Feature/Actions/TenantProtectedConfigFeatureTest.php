<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Modules\Tenant\Actions\TenantProtectedConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Modules\Tenant\Tests\TestCase;

#[CoversClass(TenantProtectedConfig::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantProtectedConfigFeatureTest extends TestCase
{
    /**
     * Test retrieving the tenant successfully.
     */
    public function testTenantProtectedConfigSuccess(): void
    {
        // Disable the privacy checks to bypass the IsInternalRequest middleware
        config(['core.privacy.disable_ip_check' => true, 'core.privacy.disable_key_check' => true]);
        
        // Bypass tenant middleware domain resolution
        $this->bypassTenantMiddleware();

        $response = $this->getJson(
            route('tenant.protected'),
        );

        $response->assertSuccessful()
            ->assertJson([
                'data' => [
                    'id'     => $this->tenant->public_id,
                    'name'   => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);
    }
}
