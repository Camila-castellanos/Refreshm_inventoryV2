<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ecommerce config
    |--------------------------------------------------------------------------
    |
    | `excluded_hosts` is a list of hostnames where the DetectMarketFromHost
    | middleware should not attempt to resolve a Market by host. Add hosts
    | like 'localhost' or '127.0.0.1' here. You may also use a simple
    | wildcard prefix like '*.example.com' to match any subdomain.
    |
    */

    'excluded_hosts' => [
        'localhost',
        '127.0.0.1',
        '::1',
        'test.swiftstock.io',
        'swiftstock.io',
        // Add other hosts/domains you want to exclude, e.g.:
        // 'dev.my-company.test',
        // '*.internal.example',
    ],
];
