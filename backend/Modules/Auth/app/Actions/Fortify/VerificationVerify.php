<?php

namespace Modules\Auth\Actions\Fortify;

use Exception;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;
use Modules\Auth\Logs\Actions\Fortify\VerificationVerifyLogs;

/**
 * Verifies an email address.
 */
class VerificationVerify
{
    public function __construct(
        private readonly FrontendService $frontendService,
        private readonly VerificationVerifyLogs $logs,
    ) {
    }

    public function __invoke(EmailVerificationRequest $request): RedirectResponse|Response
    {
        $user = $request->getUser();
        
        try {
            $request->fulfill();
            
            if ($user) {
                $this->logs->emailVerificationSuccess(
                    $user->id,
                    $user->email,
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            }

            return $this->frontendService->redirect(
                '',
                [
                    'message' => EmailStatusEnum::EMAIL_VERIFIED->value,
                ],
            );
        } catch (Exception $e) {
            if ($user) {
                $this->logs->emailVerificationFailed(
                    $user->id,
                    $user->email,
                    $e->getMessage(),
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            }
            
            return $this->frontendService->redirect(
                '',
                [
                    'message' => StatusEnum::INTERNAL_ERROR->value,
                ],
            );
        }
    }
}
