<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Services\FrontendService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Http\Requests\EmailVerificationRequest;

/**
 * Handles email verification-related actions.
 */
class EmailController extends Controller
{
    public function __construct(
        private readonly FrontendService $frontendService,
    ) {
    }

    /**
     * Redirects the user to the home route as a verification notice.
     *
     * @return RedirectResponse
     */
    public function verificationNotice(): RedirectResponse
    {
        return $this->frontendService->redirect('home');
    }

    /**
     * Handles email verification requests and redirects based on the outcome.
     *
     * @param EmailVerificationRequest $request
     * @return RedirectResponse
     */
    public function verificationVerify(EmailVerificationRequest $request): RedirectResponse
    {
        try {
            $request->fulfill();

            return $this->frontendService->redirect(
                '',
                [
                    'verify' => 'verify_ok',
                ],
            );
        } catch (Exception $e) {
            return $this->frontendService->redirect(
                '',
                [
                    'verify' => 'verify_fail',
                ],
            );
        }
    }
}
