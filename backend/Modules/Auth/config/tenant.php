<?php

return [
    'seeders' => [
        'basic'    => \Modules\Auth\Seeders\AuthBasicSeeder::class,
        'isolated' => \Modules\Auth\Seeders\AuthIsolatedSeeder::class,
    ],

    'pipes'   => [
        \Modules\Auth\Pipes\AuthConfigPipe::class,
    ],
];
