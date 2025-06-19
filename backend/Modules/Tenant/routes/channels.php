<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Tenant\Services\FindService;

Broadcast::channel(
    'tenant.{tenantPublicId}.User.{publicId}',
    function (User $user, string $tenantPublicId, string $publicId): bool {
        if (
            app(FindService::class)->getTenantPublicIdFromId(
                $user->tenant_id,
            ) !== $tenantPublicId
        ) {
            return false;
        }

        return $user->public_id === $publicId;
    }
);
