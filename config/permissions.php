<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permissions Structure
    |--------------------------------------------------------------------------
    |
    | Defines the available pages and their corresponding tabs in the system.
    | Used by the Team Manager to assign permissions to users.
    |
    */

    'structure' => [
        ['name' => 'Dashboard', 'tabs' => []],
        ['name' => 'Inventory', 'tabs' => ['Active Inventory', 'On Hold', 'Sold', 'View Organization Data']],
        ['name' => 'Markets', 'tabs' => []],
        ['name' => 'Accounting', 'tabs' => ['Expenses', 'Bills', 'Payments', 'Taxes', 'View Organization Data']],
        ['name' => 'Contacts', 'tabs' => ['Customers', 'Prospects', 'Vendors', 'Mailing list', 'Email editor', 'View Organization Data']],
        ['name' => 'Stores', 'tabs' => []],
        ['name' => 'Company', 'tabs' => []],
        ['name' => 'Users', 'tabs' => []],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Page Permissions for ADMIN/OWNER
    |--------------------------------------------------------------------------
    |
    | These are the default permissions assigned to new company owners (ADMIN)
    | or used as a fallback for administrators.
    |
    */

    'defaults' => [
        'Dashboard' => [],
        'Inventory' => ['Active Inventory', 'On Hold', 'Sold', 'View Organization Data'],
        'Accounting' => ['Payments', 'Expenses', 'Bills', 'Taxes', 'View Organization Data'],
        'Contacts' => ['Customers', 'Prospects', 'Vendors', 'Mailing list', 'Email editor', 'View Organization Data'],
        'Stores' => [],
        'Company' => [],
        'Users' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Page Permissions for regular USERs
    |--------------------------------------------------------------------------
    |
    | These are the default permissions assigned to invited members (USER role).
    | Usually more restricted than administrators.
    |
    */

    'user_defaults' => [
        'Inventory' => ['Active Inventory', 'On Hold', 'Sold'],
    ],
];
