<?php

namespace Modules\Core\Services\User;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

/**
 * Service to create users.
 */
class UserCreateService
{
    /**
     * Create a new user instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, bool $event = true): User
    {
        if (!isset($data['public_id'])) {
            $data['public_id'] = Str::ulid();
        }

        $user = User::create($data);

        if ($event) {
            event(new Registered($user));
        }

        return $user;
    }
}
