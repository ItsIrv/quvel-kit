<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\CreateClientNonceAction;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\NonceSessionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(CreateClientNonceAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class CreateClientNonceActionTest extends TestCase
{
    private Mockery\MockInterface|ClientNonceService $clientNonceService;
    private Mockery\MockInterface|NonceSessionService $nonceSessionService;
    private CreateClientNonceAction $action;

    public function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->clientNonceService  = Mockery::mock(ClientNonceService::class);
        $this->nonceSessionService = Mockery::mock(NonceSessionService::class);

        // Instantiate the action with mocked dependencies
        $this->action = new CreateClientNonceAction(
            $this->clientNonceService,
            $this->nonceSessionService,
        );
    }

    /**
     * Test that CreateClientNonceAction generates a nonce and stores it in session.
     */
    public function testCreatesClientNonce(): void
    {
        // Arrange
        $nonce = 'test-nonce-123';

        $this->clientNonceService->shouldReceive('create')
            ->once()
            ->andReturn($nonce);

        $this->nonceSessionService->shouldReceive('setNonce')
            ->once()
            ->with($nonce);

        // Act
        $response = $this->action->__invoke();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        // Verify JSON response structure
        $responseData = $response->getData(true);
        $this->assertArrayHasKey('nonce', $responseData);
        $this->assertEquals($nonce, $responseData['nonce']);
    }
}
