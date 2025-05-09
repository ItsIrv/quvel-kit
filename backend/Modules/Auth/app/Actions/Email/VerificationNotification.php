<?php

namespace Modules\Auth\Actions\Email;

use Exception;
use Modules\Auth\Enums\EmailStatusEnum;
use Modules\Auth\Http\Requests\EmailNotificationRequest;
use Illuminate\Http\RedirectResponse;
use Modules\Core\Enums\StatusEnum;
use Modules\Core\Services\FrontendService;

/**
 * Verifies an email address.
 */
class VerificationNotification
{
    public function __construct(
        private readonly FrontendService $frontendService,
    ) {
    }

    public function __invoke(EmailNotificationRequest $request): RedirectResponse
    {
        try {
            $request->fulfill();

            return $this->frontendService->redirect(
                '',
                [
                    'message' => EmailStatusEnum::EMAIL_VERIFICATION_NOTICE->value,
                ],
            );
        } catch (Exception $e) {
            return $this->frontendService->redirect(
                '',
                [
                    'message' => StatusEnum::INTERNAL_ERROR->value,
                ],
            );
        }
    }
}
