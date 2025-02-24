<?php

namespace Modules\Tenant\Actions;

use Modules\Tenant\app\Services\TenantSessionService;
use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Transformers\TenantDumpTransformer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Action to dump the current tenant.
 */
class TenantDump
{
    public function __construct(
        private TenantSessionService $sessionService,
    ) {
    }

    public function __invoke(): TenantDumpTransformer
    {
        $tenant = $this->sessionService->getTenant();

        if (!$tenant) {
            throw new NotFoundHttpException(
                TenantError::NOT_FOUND->value,
            );
        }

        return new TenantDumpTransformer($tenant);
    }
}
