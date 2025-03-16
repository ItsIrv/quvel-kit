<?php

namespace Modules\Auth\Tests\Unit\Actions\User;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\User\LogoutAction;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\AuthStatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(LogoutAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class LogoutActionTest extends TestCase
{
    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private LogoutAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);

        $this->action = new LogoutAction(
            $this->userAuthenticationService,
            $this->responseFactory,
        );
    }

    /**
     * Test that the logout action correctly logs out the user.
     */
    public function test_logout_action_logs_out_user(): void
    {
        // Arrange
        $this->userAuthenticationService->shouldReceive('logout')
            ->once()
            ->andReturnNull();

        $expectedResponse = new JsonResponse([
            'message' => AuthStatusEnum::LOGOUT_SUCCESS->getTranslatedMessage(),
        ]);

        $this->responseFactory->shouldReceive('json')
            ->once()
            ->with(['message' => AuthStatusEnum::LOGOUT_SUCCESS->getTranslatedMessage()])
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke();

        // Assert
        $this->assertSame($expectedResponse, $response);
    }
}
