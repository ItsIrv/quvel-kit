<?php

namespace Modules\Core\Tests\Unit\Actions;

use Modules\Core\Actions\QuvelWelcome;
use Modules\Core\Services\FrontendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('welcome')]
#[Group('app-actions')]
class QuvelWelcomeTest extends TestCase
{
    private QuvelWelcome $action;

    private FrontendService|MockInterface $frontendService;

    #[Before]
    public function setupTest(): void
    {
        $this->action          = new QuvelWelcome();
        $this->frontendService = Mockery::mock(FrontendService::class);
    }

    /**
     * Test that the welcome view is returned in the local environment.
     */
    public function testReturnsWelcomeViewInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $response = ($this->action)($this->frontendService);

        $this->assertInstanceOf(
            View::class,
            $response,
        );

        $this->assertEquals(
            'welcome',
            $response->name(),
        );
    }

    /**
     * Test that the action redirects to the frontend URL in production.
     */
    public function testRedirectsToFrontendUrlInProduction(): void
    {
        $url = 'https://quvel.app';

        $this->app->detectEnvironment(fn () => 'production');

        $this->frontendService->shouldReceive('redirect')
            ->once()
            ->with('')
            ->andReturn(new RedirectResponse($url));

        $response = ($this->action)($this->frontendService);

        $this->assertInstanceOf(
            RedirectResponse::class,
            $response,
        );

        $this->assertEquals(
            $url,
            $response->getTargetUrl(),
        );

        $this->assertEquals(
            302,
            $response->getStatusCode(),
        );
    }
}
