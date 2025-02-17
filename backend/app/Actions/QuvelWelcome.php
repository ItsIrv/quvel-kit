<?php

namespace App\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

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

        return redirect()->away(
            config('quvel.frontend_url'),
        );
    }
}
