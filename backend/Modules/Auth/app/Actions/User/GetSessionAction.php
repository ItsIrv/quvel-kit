<?php

namespace Modules\Auth\Actions\User;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * Action to get the user session.
 */
class GetSessionAction
{
    /**
     * Handle the action.
     */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
