<?php

namespace Tests\Unit\Actions;

use App\Actions\QuvelWelcome;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
class QuvelWelcomeTest extends TestCase
{
    /**
     * Test that the welcome view is returned in the local environment.
     */
    public function testReturnsWelcomeViewInLocalEnvironment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $action   = new QuvelWelcome();
        $response = $action();

        $this->assertInstanceOf(View::class, $response);
        $this->assertEquals('welcome', $response->name());
    }

    /**
     * Test that the action redirects to the frontend URL in production.
     */
    public function testRedirectsToFrontendUrlInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['quvel.frontend_url' => 'https://quvel.app']);

        $action   = new QuvelWelcome();
        $response = $action();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://quvel.app', $response->getTargetUrl());
        $this->assertEquals(302, $response->getStatusCode());
    }
}
