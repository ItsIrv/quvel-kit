<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\ValidateTenantSession;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Tests\TestCase;
use App\Models\User;

class ValidateTenantSessionTest extends TestCase
{
    use RefreshDatabase;

    private ValidateTenantSession $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use the tenant context from parent TestCase
        $this->middleware = new ValidateTenantSession($this->tenantContext);
    }

    public function test_it_skips_validation_when_tenant_context_is_bypassed(): void
    {
        $this->tenantContext->setBypassed(true);
        
        $request = Request::create('/test');
        $request->setLaravelSession(Session::driver());
        
        $called = false;
        $response = $this->middleware->handle($request, function () use (&$called) {
            $called = true;
            return 'response';
        });
        
        $this->assertTrue($called);
        $this->assertEquals('response', $response);
    }

    public function test_it_skips_validation_when_no_tenant_is_set(): void
    {
        // Create a fresh context without tenant
        $emptyContext = new TenantContext();
        $middleware = new ValidateTenantSession($emptyContext);
        
        $request = Request::create('/test');
        $request->setLaravelSession(Session::driver());
        
        $called = false;
        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return 'response';
        });
        
        $this->assertTrue($called);
        $this->assertEquals('response', $response);
    }

    public function test_it_stores_tenant_id_in_session_when_missing(): void
    {
        // Use the tenant from parent setup
        $tenant = $this->tenant;
        
        $request = Request::create('/test');
        $session = Session::driver();
        $request->setLaravelSession($session);
        
        $this->assertFalse($session->has('tenant_id'));
        
        $this->middleware->handle($request, function () {
            return 'response';
        });
        
        $this->assertTrue($session->has('tenant_id'));
        $this->assertEquals($tenant->id, $session->get('tenant_id'));
    }

    public function test_it_invalidates_session_when_tenant_mismatch(): void
    {
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create();
        
        // Set current tenant to tenant2
        $this->tenantContext->set($tenant2);
        
        $request = Request::create('/test');
        $session = Session::driver();
        $request->setLaravelSession($session);
        
        // Session belongs to tenant1
        $session->put('tenant_id', $tenant1->id);
        $oldToken = $session->token();
        
        $this->middleware->handle($request, function () {
            return 'response';
        });
        
        // Session should be regenerated
        $this->assertNotEquals($oldToken, $session->token());
        $this->assertEquals($tenant2->id, $session->get('tenant_id'));
    }

    public function test_it_logs_out_user_when_tenant_mismatch(): void
    {
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create();
        
        // Create user in tenant1 (current context)
        $user = User::factory()->create(['tenant_id' => $tenant1->id]);
        
        // Mock Auth facade to avoid actual authentication
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('logout')->once();
        
        // Now switch to tenant2
        $this->tenantContext->set($tenant2);
        
        $request = Request::create('/test');
        $session = Session::driver();
        $request->setLaravelSession($session);
        
        $this->middleware->handle($request, function () {
            return 'response';
        });
    }

    public function test_it_allows_valid_tenant_session(): void
    {
        $tenant = $this->tenant;
        
        // Create user in same tenant
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $request = Request::create('/test');
        $session = Session::driver();
        $request->setLaravelSession($session);
        
        // Session belongs to same tenant
        $session->put('tenant_id', $tenant->id);
        
        // Mock Auth to simulate authenticated user
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);
        
        $oldToken = $session->token();
        
        $this->middleware->handle($request, function () {
            return 'response';
        });
        
        // Session should NOT be regenerated
        $this->assertEquals($oldToken, $session->token());
        $this->assertEquals($tenant->id, $session->get('tenant_id'));
    }
}