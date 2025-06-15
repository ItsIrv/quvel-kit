<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Actions\TenantDump;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Modules\Tenant\Tests\TestCase;

#[CoversClass(TenantDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantDumpFeatureTest extends TestCase
{
    /**
     * Test retrieving the tenant successfully.
     */
    public function testTenantDumpSuccess(): void
    {
        // Disable the privacy checks to bypass the IsInternalRequest middleware
        config(['core.privacy.disable_ip_check' => true, 'core.privacy.disable_key_check' => true]);

        $response = $this->getJson(
            route('tenant'),
        );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id'     => $this->tenant->public_id,
                    'name'   => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);
    }
}
