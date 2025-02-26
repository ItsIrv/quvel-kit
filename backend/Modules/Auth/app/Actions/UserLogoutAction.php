<?php

namespace Modules\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserLogoutAction
{
    public function __invoke(): JsonResponse
    {
        Auth::logout();

        return response()->noContent();
    }
}
