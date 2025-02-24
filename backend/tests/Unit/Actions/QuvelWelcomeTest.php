<?php

namespace Tests\Unit\Actions;

use App\Actions\QuvelWelcome;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('welcome')]
class QuvelWelcomeTest extends TestCase
{
    private QuvelWelcome $action;

    #[Before]
    public function setupTest(): void
    {
        $this->action = new QuvelWelcome();
    }

    /**
     * Test that the welcome view is returned in the local environment.
     */
    public function testReturnsWelcomeViewInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $response = ($this->action)();

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
        $this->app->detectEnvironment(fn () => 'production');

        $response = ($this->action)();

        $this->assertInstanceOf(
            RedirectResponse::class,
            $response,
        );

        $this->assertEquals(
            config('quvel.frontend_url'),
            $response->getTargetUrl(),
        );

        $this->assertEquals(
            302,
            $response->getStatusCode(),
        );
    }
}
