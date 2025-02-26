<?php

namespace Modules\Auth\Actions;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class GetUserSessionAction
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
