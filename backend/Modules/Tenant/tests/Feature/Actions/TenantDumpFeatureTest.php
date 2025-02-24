<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\Enums\TenantError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(\Modules\Tenant\Actions\TenantDump::class)]
#[Group('tenant-module')]
#[Group('actions')]
class TenantDumpFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test retrieving the tenant successfully.
     */
    public function testTenantDumpSuccess(): void
    {
        $tenant = Tenant::factory()->create();

        // Simulate the session storing the tenant
        Session::put('tenant', $tenant->toArray());

        $response = $this->getJson(
            route('tenant'),
        );

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id'     => $tenant->public_id,
                    'name'   => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);
    }

    public function testTenantDumpThrowsExceptionWithoutTenantInLocal(): void
    {
        Session::forget('tenant');

        $this->app->detectEnvironment(fn () => 'local');

        $this->withoutExceptionHandling();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(TenantError::NOT_FOUND->value);

        $this->getJson(
            route('tenant'),
        );
    }

    /**
     * Test retrieving the tenant when no tenant exists.
     */
    public function testTenantDumpRedirectsWithoutTenantInProduction(): void
    {
        Session::forget('tenant');

        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->getJson(
            route('tenant'),
        );

        $response->assertRedirect(
            config('quvel.frontend_url') . '/error?message=' . urlencode(TenantError::NOT_FOUND->value),
        );
    }
}
