<?php

namespace Modules\TenantAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\TenantAdmin\Http\Requests\TenantCreateRequest;
use Modules\TenantAdmin\Http\Requests\TenantUpdateRequest;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    /**
     * Get paginated list of tenants
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $page    = $request->input('page', 1);

            $tenants = Tenant::with('parent')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data'         => $tenants->items(),
                'current_page' => $tenants->currentPage(),
                'last_page'    => $tenants->lastPage(),
                'per_page'     => $tenants->perPage(),
                'total'        => $tenants->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load tenants',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search tenants
     */
    public function search(Request $request): JsonResponse
    {
        $query   = $request->input('q', '');
        $perPage = $request->input('per_page', 10);

        $tenants = Tenant::with('parent')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('domain', 'like', "%{$query}%")
                    ->orWhere('public_id', 'like', "%{$query}%");
            })
            ->paginate($perPage);

        return response()->json([
            'data'         => $tenants->items(),
            'current_page' => $tenants->currentPage(),
            'last_page'    => $tenants->lastPage(),
            'per_page'     => $tenants->perPage(),
            'total'        => $tenants->total(),
        ]);
    }

    /**
     * Get single tenant
     */
    public function show($id): JsonResponse
    {
        $tenant = Tenant::where('id', $id)
            ->orWhere('public_id', $id)
            ->with('parent')
            ->firstOrFail();

        return response()->json(new TenantDumpResource($tenant));
    }

    /**
     * Create new tenant
     */
    public function store(TenantCreateRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Generate public_id if not provided
        $publicId = Str::slug($data['name']) . '-' . Str::random(6);

        // Create the tenant
        $tenant = Tenant::create([
            'public_id' => $publicId,
            'name'      => $data['name'],
            'domain'    => $data['domain'],
            'parent_id' => $data['parent_id'] ?? null,
            'config'    => new DynamicTenantConfig([
                'database_name' => $data['database'] ?? 'tenant_' . Str::slug($data['name']),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data'    => new TenantDumpResource($tenant),
        ], 201);
    }

    /**
     * Update tenant
     */
    public function update(TenantUpdateRequest $request, $id): JsonResponse
    {
        $tenant = Tenant::where('id', $id)
            ->orWhere('public_id', $id)
            ->firstOrFail();

        $data = $request->validated();

        // Update tenant fields
        if (isset($data['name'])) {
            $tenant->name = $data['name'];
        }

        if (isset($data['domain'])) {
            $tenant->domain = $data['domain'];
        }

        // Update config if status is provided
        if (isset($data['status'])) {
            $config = $tenant->config ?? new DynamicTenantConfig();
            $config->set('status', $data['status']);
            $tenant->config = $config;
        }

        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'data'    => new TenantDumpResource($tenant),
        ]);
    }

    /**
     * Delete tenant
     */
    public function destroy($id): JsonResponse
    {
        $tenant = Tenant::where('id', $id)
            ->orWhere('public_id', $id)
            ->firstOrFail();

        // Check if tenant has children
        if ($tenant->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete tenant with child tenants',
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully',
        ]);
    }
}
