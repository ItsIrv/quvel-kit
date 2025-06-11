<?php

namespace Modules\Core\Tests\Unit\Services\User;

use App\Models\User;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UserFindService::class)]
#[Group('core-module')]
#[Group('core-services')]
class UserFindServiceTest extends TestCase
{
    private UserFindService $userFindService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFindService = new UserFindService();
        $this->user            = User::factory()->create([
            'name'      => 'John Doe',
            'email'     => 'johndoe@example.com',
            'tenant_id' => $this->tenant->id,
            'public_id' => 'user_123456789',
        ]);
    }

    /**
     * Test finding a user by ID.
     */
    public function testFindByIdReturnsUser(): void
    {
        $foundUser = $this->userFindService->findById($this->user->id);

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
     * Test finding a user by public ID.
     */
    public function testFindByPublicIdReturnsUser(): void
    {
        $foundUser = $this->userFindService->findByPublicId($this->user->public_id);
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->public_id, $foundUser->public_id);
    }

    /**
     * Test findByPublicId returns null when user is not found.
     */
    public function testFindByPublicIdReturnsNullWhenUserNotFound(): void
    {
        $foundUser = $this->userFindService->findByPublicId('nonexistent_public_id');
        $this->assertNull($foundUser);
    }

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
