<?php

namespace Modules\Auth\Actions\Fortify;

use Exception;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailNotificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;
use Modules\Auth\Logs\Actions\Fortify\VerificationNotificationLogs;

/**
 * Verifies an email address.
 */
class VerificationNotification
{
    public function __construct(
        private readonly FrontendService $frontendService,
        private readonly VerificationNotificationLogs $logs,
    ) {
    }

    public function __invoke(EmailNotificationRequest $request): RedirectResponse|Response
    {
        $user = $request->user();
        
        try {
            $request->fulfill();
            
            if ($user) {
                $this->logs->verificationNotificationSent(
                    $user->id,
                    $user->email,
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            }

            return $this->frontendService->redirect(
                '',
                [
                    'message' => EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value,
                ],
            );
        } catch (Exception $e) {
            if ($user) {
                $this->logs->verificationNotificationFailed(
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
