<?php

namespace Modules\Core\Actions;

use Illuminate\Contracts\View\View;

/**
 * Returns the welcome view.
 */
class QuvelWelcome
{
    public function __invoke(): View
    {
        return view('welcome');
    }
}
