<?php

namespace Modules\Core\Tests\Unit\Services\User;

use Modules\Core\Services\User\UserCreateService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

#[CoversClass(UserCreateService::class)]
#[Group('core-module')]
#[Group('core-services')]
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
        $this->hasherMock        = $this->createMock(Hasher::class);
        $this->userCreateService = new UserCreateService();
    }

    /**
     * Test that create successfully creates a user with hashed password.
     */
    public function testCreateUserSuccessfully(): void
    {
        $name  = $this->faker->name;
        $email = $this->faker->email;

        $userData = [
            'public_id' => Str::ulid(),
            'name'      => $name,
            'email'     => $email,
            'password'  => 'password123',
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
            'public_id' => $user->public_id,
            'name'      => $name,
            'email'     => $email,
        ]);
    }

    /**
     * Test that create automatically generates a public_id when not provided.
     */
    public function testCreateUserAutoGeneratesPublicId(): void
    {
        $name  = $this->faker->name;
        $email = $this->faker->email;

        $userData = [
            'name'     => $name,
            'email'    => $email,
            'password' => 'password123',
        ];

        // Create the user without providing public_id
        $user = $this->userCreateService->create($userData, false); // Disable event to simplify test

        $this->assertNotNull($user->public_id);
        $this->assertInstanceOf(Ulid::class, $user->public_id);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);

        // Assert it exists in the database with the auto-generated public_id
        $this->assertDatabaseHas('users', [
            'public_id' => $user->public_id,
            'name'      => $name,
            'email'     => $email,
        ]);
    }
}
