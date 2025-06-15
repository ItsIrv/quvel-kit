<?php

namespace Modules\TenantAdmin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Models\Tenant;
use Modules\TenantAdmin\Http\Resources\TenantAdminResource;
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
                'data'         => TenantAdminResource::collection($tenants->items()),
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
            'data'         => TenantAdminResource::collection($tenants->items()),
            'current_page' => $tenants->currentPage(),
            'last_page'    => $tenants->lastPage(),
            'per_page'     => $tenants->perPage(),
            'total'        => $tenants->total(),
        ]);
    }

    /**
     * Get single tenant
     */
    public function show(mixed $id): JsonResponse
    {
        $tenant = Tenant::where('id', $id)
            ->orWhere('public_id', $id)
            ->with('parent')
            ->firstOrFail();

        return response()->json(new TenantAdminResource($tenant));
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
            'data'    => new TenantAdminResource($tenant),
        ], 201);
    }

    /**
     * Update tenant
     */
    public function update(TenantUpdateRequest $request, mixed $id): JsonResponse
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

        if (isset($data['config'])) {
            // Get existing config or create new one with proper initialization
            $config = $tenant->config;
            /** @phpstan-ignore-next-line booleanNot.alwaysFalse,booleanNot.exprNotBoolean */
            if (!$config) {
                $config = new DynamicTenantConfig();
            }

            // Merge the new config values into the existing config
            foreach ($data['config'] as $key => $value) {
                // Handle empty strings by removing the key instead of setting empty value
                if ($value === '' || $value === null) {
                    $config->forget($key);
                } else {
                    $config->set($key, $value);
                }
            }

            $tenant->config = $config;
        }

        if (isset($data['is_active'])) {
            /** @phpstan-ignore-next-line property.notFound */
            $tenant->is_active = $data['is_active'];
        }

        $tenant->save();

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'data'    => new TenantAdminResource($tenant),
        ]);
    }

    /**
     * Delete tenant
     */
    public function destroy(mixed $id): JsonResponse
    {
        $tenant = Tenant::where('id', $id)
            ->orWhere('public_id', $id)
            ->firstOrFail();

        // Check if tenant has children
        /** @phpstan-ignore-next-line staticMethod.dynamicCall */
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
