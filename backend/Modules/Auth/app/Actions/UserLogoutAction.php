<?php

namespace Modules\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Action to logout a user.
 */
class UserLogoutAction
{
    /**
     * Handle the action.
     *
     * TODO: call serivce.
     */
    public function __invoke(): JsonResponse
    {
        Auth::logout();

        return response()->noContent();
    }
}
