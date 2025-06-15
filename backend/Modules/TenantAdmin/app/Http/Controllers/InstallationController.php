<?php

namespace Modules\TenantAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\TenantAdmin\Http\Requests\InstallRequest;
use Modules\TenantAdmin\Services\InstallationService;

class InstallationController extends Controller
{
    public function __construct(
        private InstallationService $installationService,
    ) {
    }

    /**
     * Check installation status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'installed'                => $this->installationService->isInstalled(),
            'method'                   => $this->installationService->getInstallationMethod(),
            'has_env_credentials'      => $this->installationService->hasEnvCredentials(),
            'has_database_credentials' => $this->installationService->hasDatabaseCredentials(),
        ]);
    }

    /**
     * Process installation
     */
    public function install(InstallRequest $request): JsonResponse
    {
        // Guard against reinstallation
        if ($this->installationService->isInstalled()) {
            return response()->json([
                'success' => false,
                'message' => 'TenantAdmin is already installed.',
            ], 403);
        }

        try {
            $data = $request->validated();

            if ($data['installation_method'] === 'database') {
                // Create table if it doesn't exist
                $this->installationService->createCredentialsTable();

                // Create database entry
                DB::table('tenant_admin_credentials')->insert([
                    'username'   => $data['username'],
                    'password'   => Hash::make($data['password']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Update .env file
                $this->updateEnvironmentFile([
                    'TENANT_ADMIN_USERNAME' => $data['username'],
                    'TENANT_ADMIN_PASSWORD' => $data['password'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'TenantAdmin installed successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update .env file
     */
    /**
     * @param array<string, mixed> $values
     */
    private function updateEnvironmentFile(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            // Create .env from .env.example if it doesn't exist
            $examplePath = base_path('.env.example');
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern     = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
