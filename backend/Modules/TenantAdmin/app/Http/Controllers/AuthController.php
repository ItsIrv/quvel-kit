<?php

namespace Modules\TenantAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\TenantAdmin\Http\Requests\LoginRequest;
use Modules\TenantAdmin\Services\AuthenticationService;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {
    }

    /**
     * Handle login request
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->authenticate(
            $credentials['username'],
            $credentials['password']
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Start session
        $request->session()->regenerate();
        $request->session()->put('tenant_admin_authenticated', true);
        $request->session()->put('tenant_admin_user', $result['user']);

        // Handle remember me
        if ($request->boolean('remember')) {
            $request->session()->put('tenant_admin_remember', true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'redirect_url' => '/admin/tenants/dashboard',
            'user' => $result['user'],
        ]);
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('tenant_admin_authenticated');
        $request->session()->forget('tenant_admin_user');
        $request->session()->forget('tenant_admin_remember');
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
            'redirect_url' => '/admin/tenants/login',
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->session()->get('tenant_admin_user');

        if (!$user) {
            return response()->json([
                'message' => 'Not authenticated.',
            ], 401);
        }

        return response()->json($user);
    }
}
