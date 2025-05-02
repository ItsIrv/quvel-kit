<?php

namespace App\Actions;

use Modules\Core\Services\FrontendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Returns the welcome view in local, or redirects to the frontend URL in production.
 */
class QuvelWelcome
{
    public function __invoke(
        FrontendService $frontendService,
    ): View|RedirectResponse|Response {
        if (app()->isLocal()) {
            return view('welcome');
        }

        return $frontendService->redirect('');
    }
}
