<?php

namespace Tests\Unit\Providers;

use Illuminate\Http\Request;
use App\Providers\AppServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(AppServiceProvider::class)]
#[Group('app-providers')]
class AppServiceProviderTest extends TestCase
{
    public function testBootSetsHttpsServerValue(): void
    {
        $request = Request::create('http://example.com');
        $this->app->instance('request', $request);

        $provider = new AppServiceProvider($this->app);
        $provider->boot();

        $this->assertEquals('on', $request->server->get('HTTPS'));
    }
}
