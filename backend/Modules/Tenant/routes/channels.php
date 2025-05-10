<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Tenant\Services\TenantFindService;

Broadcast::channel(
    'tenant.{tenantPublicId}.User.{publicId}',
    function (User $user, string $tenantPublicId, string $publicId): bool {
        if (
            app(TenantFindService::class)->getTenantPublicIdFromId(
                $user->tenant_id,
            ) !== $tenantPublicId
        ) {
            return false;
        }

        return $user->public_id === $publicId;
    }
);

Broadcast::channel(
    'tenant.{tenantPublicId}.chat',
    function (User $user, string $tenantPublicId): array|bool {
        if (
            app(TenantFindService::class)->getTenantPublicIdFromId(
                $user->tenant_id,
            ) !== $tenantPublicId
        ) {
            return false;
        }

        return [
            'id'    => $user->public_id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
);
