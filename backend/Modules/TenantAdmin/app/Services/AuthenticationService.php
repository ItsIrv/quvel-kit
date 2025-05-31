<?php

namespace Modules\TenantAdmin\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthenticationService
{
    public function __construct(
        private InstallationService $installationService
    ) {}

    /**
     * Authenticate user against configured credentials
     */
    public function authenticate(string $username, string $password): array
    {
        $method = $this->installationService->getInstallationMethod();

        if ($method === 'env') {
            return $this->authenticateWithEnv($username, $password);
        }

        if ($method === 'database') {
            return $this->authenticateWithDatabase($username, $password);
        }

        return [
            'success' => false,
            'message' => 'No authentication method configured.',
        ];
    }

    /**
     * Authenticate using environment variables
     */
    private function authenticateWithEnv(string $username, string $password): array
    {
        $configUsername = config('tenantadmin.admin_username');
        $configPassword = config('tenantadmin.admin_password');

        if ($username === $configUsername && $password === $configPassword) {
            return [
                'success' => true,
                'user' => [
                    'id' => 1,
                    'username' => $username,
                    'method' => 'env',
                ],
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid credentials.',
        ];
    }

    /**
     * Authenticate using database
     */
    private function authenticateWithDatabase(string $username, string $password): array
    {
        try {
            $user = DB::table('tenant_admin_credentials')
                ->where('username', $username)
                ->first();

            if ($user && Hash::check($password, $user->password)) {
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'method' => 'database',
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ],
                ];
            }
        } catch (\Exception $e) {
            // Table might not exist
        }

        return [
            'success' => false,
            'message' => 'Invalid credentials.',
        ];
    }

    /**
     * Check if a session is authenticated
     */
    public function isAuthenticated($session): bool
    {
        return $session->get('tenant_admin_authenticated', false) === true;
    }

    /**
     * Get the authenticated user from session
     */
    public function getAuthenticatedUser($session): ?array
    {
        if (!$this->isAuthenticated($session)) {
            return null;
        }

        return $session->get('tenant_admin_user');
    }
}