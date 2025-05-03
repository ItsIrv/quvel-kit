<?php

namespace Modules\Core\Tests\Feature\Actions;

use Modules\Core\Actions\QuvelWelcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('welcome')]
#[Group('app-actions')]
class QuvelWelcomeFeatureTest extends TestCase
{
    /**
     * Test that the welcome route returns the view in a local environment.
     */
    public function testWelcomeRouteReturnsViewInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $response = $this->get(
            route('welcome'),
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('welcome');
    }

    /**
     * Test that the welcome route redirects to the frontend URL in production.
     */
    public function testWelcomeRouteRedirectsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get(
            route('welcome'),
        );

        $response->assertRedirect(
            config('quvel.frontend_url'),
        );
    }
}
