<?php

namespace App\Actions;

use App\Services\FrontendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Returns the welcome view in local, or redirects to the frontend URL in production.
 */
class QuvelWelcome
{
    public function __invoke(
        FrontendService $frontendService,
    ): View|RedirectResponse {
        if (app()->isLocal()) {
            return view('welcome');
        }

        return $frontendService->redirectPage('');
    }
}
