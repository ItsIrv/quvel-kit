<?php

namespace Modules\Core\Tests\Unit\Actions;

use Modules\Core\Actions\QuvelWelcome;
use Modules\Core\Services\FrontendService;
use Illuminate\Contracts\View\View;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(QuvelWelcome::class)]
#[Group('core-module')]
#[Group('core-actions')]
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
     * Test that the action returns the welcome view in production.
     */
    public function testReturnsWelcomeViewInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

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
}
