<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Illuminate\Http\RedirectResponse;
use Mockery;
use Mockery\MockInterface;
use Modules\Auth\Actions\Fortify\PasswordViewRedirect;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(PasswordViewRedirect::class)]
#[Group('auth-module')]
#[Group('auth-fortify')]
class PasswordViewRedirectTest extends TestCase
{
    public function testInvokeRedirectsToFrontend(): void
    {
        // Create a mock redirect response
        $mockResponse = Mockery::mock(RedirectResponse::class);

        // Mock the FrontendService
        $mockFrontendService = $this->mock(FrontendService::class, function (MockInterface $mock) use ($mockResponse) {
            $mock->shouldReceive('redirect')
                ->once()
                ->with('', [
                    'form'  => 'password-reset',
                    'token' => 'test-token',
                ])
                ->andReturn($mockResponse);
        });

        // Create the action
        $action = new PasswordViewRedirect($mockFrontendService);

        // Call the action
        $response = $action('test-token');

        // Assert that the response is the one returned by the frontend service
        $this->assertSame($mockResponse, $response);
    }
}
