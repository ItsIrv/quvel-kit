<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(User::class)]
#[Group('user-module')]
#[Group('user-models')]
class UserTest extends TestCase
{
    /**
     * Test if a User model can be instantiated.
     */
    public function test_user_model_instantiation(): void
    {
        $user = new User();
        $this->assertInstanceOf(
            User::class,
            $user,
        );
    }

    /**
     * Test if the fillable attributes are set correctly.
     */
    public function test_fillable_attributes(): void
    {
        $user = new User();
        $expected = [
            'public_id',
            'name',
            'email',
            'password',
            'provider_id',
            'avatar',
        ];

        $this->assertEquals(
            $expected,
            $user->getFillable(),
        );
    }

    /**
     * Test if the hidden attributes are set correctly.
     */
    public function test_hidden_attributes(): void
    {
        $user = new User();
        $expected = [
            'password',
            'remember_token',
        ];

        $this->assertEquals(
            $expected,
            $user->getHidden(),
        );
    }

    /**
     * Test if the casts are set correctly.
     */
    public function test_casts(): void
    {
        $user = new User();
        $expected = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'id' => 'int',
        ];

        $this->assertEquals(
            $expected,
            $user->getCasts(),
        );
    }

    /**
     * Test factory usage to generate a user.
     */
    public function test_user_factory_creates_a_user(): void
    {
        // TODO: Create trait for mocking TenantContext.
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function test_tenant(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new User())->tenant());
    }
}
