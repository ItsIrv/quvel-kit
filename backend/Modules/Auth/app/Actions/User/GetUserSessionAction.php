<?php

namespace Modules\Auth\Actions\User;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * Action to get the user session.
 */
class GetUserSessionAction
{
    /**
     * Handle the action.
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\UserResource
     */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
