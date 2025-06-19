<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Actions\Fortify\ForgotPassword;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ForgotPassword::class)]
#[Group('auth-module')]
#[Group('auth-fortify')]
class ForgotPasswordTest extends TestCase
{
    public function testInvokeCallsPasswordResetLinkController(): void
    {
        // Create a mock response
        $mockResponse = Mockery::mock(Responsable::class);

        // Mock the PasswordResetLinkController
        $mockController = $this->mock(PasswordResetLinkController::class, function (MockInterface $mock) use ($mockResponse) {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn($mockResponse);
        });

        // Create the action
        $action = new ForgotPassword($mockController);

        // Create a request
        $request = new Request();

        // Call the action
        $response = $action($request);

        // Assert that the response is the one returned by the controller
        $this->assertSame($mockResponse, $response);
    }
}
