<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use App\Services\FrontendService;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(AppServiceProvider::class)]
#[BackupGlobals(false)]
#[Group('providers')]
class AppServiceProviderTest extends TestCase
{
    /**
     * Runs before each test.
     */
    #[Before]
    public function setupTest(): void
    {
        URL::spy();
    }

    /**
     * Ensures the register method binds correct services.
     */
    public function testRegisterMethodRuns(): void
    {
        $this->assertTrue(
            $this->app->bound(
                FrontendService::class,
            ),
        );
    }

    /**
     * Ensures boot forces HTTPS.
     */
    #[Group('security')]
    public function testBootForcesHttps(): void
    {
        URL::shouldReceive('forceScheme')
            ->once()
            ->with('https');

        $this->app->getProvider(AppServiceProvider::class)->boot();

        URL::shouldHaveReceived('forceScheme')
            ->once()
            ->with('https');
    }
}
