<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'], 

    'allowed_origins' => [
        'https://smartspend-fe.vercel.app', 
        'http://localhost:5173'
    ], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'allowed_methods' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, 
];