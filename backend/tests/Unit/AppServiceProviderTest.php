<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use URL;

#[CoversClass(AppServiceProvider::class)]
#[BackupGlobals(false)]
class AppServiceProviderTest extends TestCase
{
    private AppServiceProvider $provider;

    /**
     * Runs before each test.
     */
    #[Before]
    public function setupTest(): void
    {
        $this->provider = new AppServiceProvider($this->app);

        URL::spy();
    }

    /**
     * Ensures the register method executes without errors.
     */
    public function testRegisterMethodRuns(): void
    {
        $this->provider->register();
        $this->assertTrue(true);
    }

    /**
     * Ensures boot runs without errors.
     */
    public function testBootRuns(): void
    {
        $this->provider->boot();
        $this->assertTrue(true);
    }

    /**
     * Ensures boot forces HTTPS.
     */
    #[Depends('testBootRuns')]
    #[Group('security')]
    public function testBootForcesHttps(): void
    {
        URL::shouldReceive('forceScheme')
            ->once()
            ->with('https');

        $this->provider->boot();
    }
}
