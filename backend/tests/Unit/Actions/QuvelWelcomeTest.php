<?php

namespace Tests\Unit\Actions;

use App\Actions\QuvelWelcome;
use App\Services\FrontendService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
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

    #[Before]
    public function setupTest(): void
    {
        $this->action = new QuvelWelcome;
    }

    /**
     * Test that the welcome view is returned in the local environment.
     */
    public function test_returns_welcome_view_in_local_environment(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $response = ($this->action)(
            new FrontendService('https://quvel.app'),
        );

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
    public function test_redirects_to_frontend_url_in_production(): void
    {
        $url = 'https://quvel.app';
        $this->app->detectEnvironment(fn () => 'production');

        $response = ($this->action)(
            new FrontendService($url),
        );

        $this->assertInstanceOf(
            RedirectResponse::class,
            $response,
        );

        $this->assertEquals(
            "$url/",
            $response->getTargetUrl(),
        );

        $this->assertEquals(
            302,
            $response->getStatusCode(),
        );
    }
}
