<?php

namespace Tests\Feature\Actions;

use App\Actions\QuvelWelcome;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('welcome')]
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

        $response->assertStatus(\Symfony\Component\HttpFoundation\Response::HTTP_OK);
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
