<?php

use Modules\Tenant\Factories\TenantConfigFactory;

return [
    'seeders'    => [
        // Tenant module doesn't contribute tenant-specific seeders
        // but this shows the structure for future use
    ],

    'pipes'      => [
        // All the core tenant configuration pipes
        \Modules\Tenant\Pipes\CoreConfigPipe::class,
        \Modules\Tenant\Pipes\DatabaseConfigPipe::class,
        \Modules\Tenant\Pipes\CacheConfigPipe::class,
        \Modules\Tenant\Pipes\RedisConfigPipe::class,
        \Modules\Tenant\Pipes\SessionConfigPipe::class,
        \Modules\Tenant\Pipes\MailConfigPipe::class,
        \Modules\Tenant\Pipes\QueueConfigPipe::class,
        \Modules\Tenant\Pipes\FilesystemConfigPipe::class,
        \Modules\Tenant\Pipes\BroadcastingConfigPipe::class,
        \Modules\Tenant\Pipes\LoggingConfigPipe::class,
        \Modules\Tenant\Pipes\ServicesConfigPipe::class,
    ],

    'tables'     => [
        'users' => \Modules\Tenant\Tables\UsersTableConfig::class,
    ],

    'exclusions' => [
        'paths'    => [],
        'patterns' => [],
    ],
];
