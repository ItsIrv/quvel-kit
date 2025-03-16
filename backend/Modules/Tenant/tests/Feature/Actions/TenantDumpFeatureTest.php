<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Enums\TenantError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantDumpFeatureTest extends TestCase
{
    /**
     * Test retrieving the tenant successfully.
     */
    public function test_tenant_dump_success(): void
    {
        $response = $this->getJson(
            route('tenant'),
        );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->tenant->public_id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ],
            ]);
    }

    /**
     * Test retrieving the tenant fails when tenant is incorrect or does not exist.
     */
    public function test_tenant_dump_throws_exception_without_tenat(): void
    {
        // Simulate incorrect tenant by deleting all.
        DB::table('tenants')->delete();

        $this->withoutExceptionHandling();
        $this->expectException(Exception::class);

        $this->withoutExceptionHandling();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(TenantError::NOT_FOUND->value);

        $this->getJson(
            route('tenant'),
        );
    }
}
