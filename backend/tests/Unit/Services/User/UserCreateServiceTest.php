<?php

namespace Tests\Unit\Services\User;

use App\Services\User\UserCreateService;
use Illuminate\Contracts\Hashing\Hasher;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(UserCreateService::class)]
#[Group('user-module')]
#[Group('user-services')]
class UserCreateServiceTest extends TestCase
{
    private UserCreateService $userCreateService;

    private Hasher|MockObject $hasherMock;

    /**
     * @throws Exception
     */
    #[Before]
    public function setupTest(): void
    {
        $this->hasherMock = $this->createMock(Hasher::class);
        $this->userCreateService = new UserCreateService;
    }

    /**
     * Test that create successfully creates a user with hashed password.
     */
    public function test_create_user_successfully(): void
    {
        $name = $this->faker->name;
        $email = $this->faker->email;

        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
        ];

        // Mock hashing behavior
        $this->hasherMock->expects($this->once())
            ->method('make')
            ->with('password123')
            ->willReturn('hashed-password');

        $userData['password'] = $this->hasherMock->make($userData['password']);

        // Create the user
        $user = $this->userCreateService->create($userData);

        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);

        // Assert it exists in the database
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $name,
            'email' => $email,
        ]);
    }
}
