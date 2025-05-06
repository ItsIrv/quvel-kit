<?php

namespace Modules\Core\Tests\Unit\Providers;

use Illuminate\Http\Request;
use Modules\Core\Providers\CoreServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(CoreServiceProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class CoreServiceProviderTest extends TestCase
{
    public function testBootSetsHttpsServerValue(): void
    {
        $request = Request::create('http://example.com');
        $this->app->instance('request', $request);

        $provider = new CoreServiceProvider($this->app);
        $provider->boot();

        $this->assertEquals('on', $request->server->get('HTTPS'));
    }
}
