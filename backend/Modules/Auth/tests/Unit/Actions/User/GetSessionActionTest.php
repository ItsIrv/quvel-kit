<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use Modules\Core\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery;
use Modules\Auth\Actions\User\GetSessionAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(GetSessionAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class GetSessionActionTest extends TestCase
{
    private GetSessionAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetSessionAction();
    }

    /**
     * Test that the session action returns the correct user resource.
     */
    public function testGetSessionReturnsUserResource(): void
    {
        // Arrange
        $user    = User::factory()->make();
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->once()->andReturn($user);

        // Act
        $result = $this->action->__invoke($request);

        // Assert
        $this->assertEquals(new UserResource($user), $result);
    }
}
