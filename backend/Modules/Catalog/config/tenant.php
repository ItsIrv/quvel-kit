<?php

return [
    'tables' => [
        'catalog_items' => [
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
                // Add any unique constraints that need to be dropped here
            ],

            /**
             * Unique constraints that should include tenant_id
             * Each entry is an array of columns that should be unique together within a tenant
             */
            'tenant_unique_constraints' => [
                // Add any tenant-specific unique constraints here
            ],
        ],
    ],
];
