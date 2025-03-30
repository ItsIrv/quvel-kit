<?php

return [
    'name' => 'Tenant',
    'tables' => [
        'users' => [
            'after' => 'id',
            'cascadeDelete' => true,
            'dropUnique' => [
                'email',
                'provider_id',
            ],
            'compoundUnique' => [
                'email',
                'provider_id',
            ],

        ],
        'catalog_items' => [
            'after' => 'id',
            'cascadeDelete' => true,
        ],
    ],
];
