<?php

return [
    'seeders' => [
        'basic'    => \Modules\Core\Seeders\CoreBasicSeeder::class,
        'isolated' => \Modules\Core\Seeders\CoreIsolatedSeeder::class,
    ],

    // Additional seeders for shared configs across all templates
    'shared_seeders' => [
        'recaptcha' => \Modules\Core\Seeders\RecaptchaSharedSeeder::class,
        'pusher'    => \Modules\Core\Seeders\PusherSharedSeeder::class,
    ],
];
