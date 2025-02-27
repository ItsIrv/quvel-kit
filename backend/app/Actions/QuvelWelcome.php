<?php

namespace App\Actions;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Returns the welcome view in local, or redirects to the frontend URL in production.
 */
class QuvelWelcome
{
    public function __invoke(): View|RedirectResponse
    {
        if (app()->environment('local')) {
            return view('welcome');
        }

        /**
         * @var string
         */
        $frontendUrl = config('quvel.frontend_url');

        return redirect()->away($frontendUrl);
    }
}
