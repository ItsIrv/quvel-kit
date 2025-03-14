<?php

namespace Modules\User\Tests\Unit\Services\User;

use App\Models\User;
use App\Services\User\UserFindService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UserFindService::class)]
#[Group('user-module')]
#[Group('user-services')]
class UserFindServiceTest extends TestCase
{
    private UserFindService $userFindService;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->userFindService = new UserFindService();
        $this->user            = User::factory()->create([
            'name'      => 'John Doe',
            'email'     => 'johndoe@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /**
     * Test finding a user by ID.
     */
    public function testFindByIdReturnsUser(): void
    {
        $foundUser = $this->userFindService->findById($this->user->id);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
    }

    /**
     * Test that findById throws ModelNotFoundException for non-existing user.
     */
    public function testFindByIdThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->userFindService->findById(9999);
    }

    /**
     * Test finding a user by email.
     */
    public function testFindByEmailReturnsUser(): void
    {
        $foundUser = $this->userFindService->findByEmail($this->user->email);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    /**
     * Test findByEmail returns null when user is not found.
     */
    public function testFindByEmailReturnsNullWhenUserNotFound(): void
    {
        $foundUser = $this->userFindService->findByEmail('nonexistent@example.com');
        $this->assertNull($foundUser);
    }

    /**
     * Test finding a user by username.
     */
    // public function testFindByUsernameReturnsUser(): void
    // {
    //     $foundUser = $this->userFindService->findByUsername($this->user->username);
    //     $this->assertInstanceOf(User::class, $foundUser);
    //     $this->assertEquals($this->user->username, $foundUser->username);
    // }

    /**
     * Test findByUsername returns null when user is not found.
     */
    // public function testFindByUsernameReturnsNullWhenUserNotFound(): void
    // {
    //     $foundUser = $this->userFindService->findByUsername('nonexistentuser');
    //     $this->assertNull($foundUser);
    // }

    /**
     * Test finding a user by any field.
     */
    public function testFindByFieldReturnsUser(): void
    {
        $foundUser = $this->userFindService->findByField('email', $this->user->email);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    /**
     * Test findByField returns null when user is not found.
     */
    public function testFindByFieldReturnsNullWhenUserNotFound(): void
    {
        $foundUser = $this->userFindService->findByField('email', 'nonexistent@example.com');
        $this->assertNull($foundUser);
    }
}
