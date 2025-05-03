<?php

namespace Modules\Core\Tests\Feature\Actions;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View as ViewContract;
use Modules\Core\Actions\QuvelWelcome;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('core-module')]
#[Group('core-actions')]
class QuvelWelcomeFeatureTest extends TestCase
{
    /**
     * Test that the welcome route returns the view in a local environment.
     */
    public function testWelcomeRouteReturnsViewInLocal(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $fakeView = $this->createMock(ViewContract::class);
        $fakeView->method('render')->willReturn('fake html');

        $viewFactory = $this->createMock(ViewFactory::class);
        $viewFactory->method('make')->with('welcome', [], [])->willReturn($fakeView);

        $this->app->instance(ViewFactory::class, $viewFactory);

        $response = $this->get(route('welcome'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertSee('fake html');
    }

    /**
     * Test that the welcome route redirects to the frontend URL in production.
     */
    public function testWelcomeRouteRedirectsInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->get(route('welcome'));

        $response->assertRedirect(config('quvel.frontend_url'));
    }
}
