<?php

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Services\FindService;

/**
 * Public Tenant Controller
 *
 * Provides public access to tenant configuration for specific domains.
 * Used by frontend applications to get tenant config without authentication.
 */
class TenantPublicController extends Controller
{
    public function __construct(
        private readonly FindService $findService,
    ) {
    }

    /**
     * Get public tenant configuration by domain.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $domain = $request->query('domain');

        if (!$domain) {
            return response()->json([
                'error' => 'Domain parameter is required',
            ], 400);
        }

        try {
            // Find tenant by domain
            $tenant = $this->findService->findTenantByDomain($domain);

            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant not found',
                ], 404);
            }

            // Check if tenant allows public config API
            $allowPublicConfig = $tenant->getEffectiveConfig()?->get('allow_public_config_api', false);

            if (!$allowPublicConfig) {
                return response()->json([
                    'error' => 'Public config API not enabled for this tenant',
                ], 403);
            }

            // Return tenant config using existing resource
            return response()->json([
                'data' => new TenantDumpResource($tenant),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve tenant configuration',
            ], 500);
        }
    }
}
