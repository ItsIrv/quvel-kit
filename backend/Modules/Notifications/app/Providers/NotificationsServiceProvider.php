<?php

namespace Modules\Notifications\Providers;

use App\Providers\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notifications';

    protected string $nameLower = 'notifications';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
