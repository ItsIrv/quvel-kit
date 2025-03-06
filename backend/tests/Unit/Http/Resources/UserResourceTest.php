<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UserResource::class)]
#[Group('user-module')]
#[Group('user-resources')]
class UserResourceTest extends TestCase
{
    /**
     * Test that the resource transforms a user model correctly.
     */
    public function testToArrayTransformsUserCorrectly(): void
    {
        $name  = $this->faker->name;
        $email = $this->faker->email;

        $user = User::factory()->make();
        $user->setRawAttributes([
            'id'         => 'public-id-1',
            'name'       => $name,
            'email'      => $email,
            'avatar'     => $user->avatar,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], true);

        $resource = new UserResource($user);
        $result   = $resource->toArray(new Request());

        $this->assertEquals([
            'id'         => 0,
            'name'       => $name,
            'email'      => $email,
            'avatar'     => $user->avatar,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], $result);
    }
}
