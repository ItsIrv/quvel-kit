<?php

namespace Tests\Unit;

use Illuminate\Foundation\Application;
use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(AppServiceProvider::class)]
class AppServiceProviderTest extends TestCase
{
    /**
     * Test if register method runs.
     */
    public function testRegisterMethodRuns(): void
    {
        $app      = $this->createMock(Application::class);
        $provider = new AppServiceProvider($app);

        $provider->register(); // Actually call the method

        $this->assertTrue(true); // Ensure PHPUnit does not skip this test
    }

    /**
     * Test if boot method runs.
     */
    public function testBootMethodRuns(): void
    {
        $app      = $this->createMock(Application::class);
        $provider = new AppServiceProvider($app);

        $provider->boot(); // Actually call the method

        $this->assertTrue(true); // Ensure PHPUnit does not skip this test
    }
}
