<?php

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
        'users' => [
            /**
             * Column after which the tenant_id should be added
             */
            'after'                     => 'id',

            /**
             * Whether tenant deletion cascades to this table
             */
            'cascade_delete'            => true,

            /**
             * List of individual unique constraints to drop before adding tenant-specific compound keys
             */
            'drop_uniques'              => [
                ['email'],
                ['provider_id'],
            ],

            /**
             * Unique constraints that should include tenant_id
             * Each entry is an array of columns that should be unique together within a tenant
             */
            'tenant_unique_constraints' => [
                ['email'],
                ['provider_id'],
                ['email', 'provider_id'],
            ],
        ],
    ],

    'exclusions' => [
        'paths'    => [
            // Tenant-specific paths that should bypass tenant resolution
        ],
        'patterns' => [
            // Tenant-specific patterns that should bypass tenant resolution
        ],
    ],
];
