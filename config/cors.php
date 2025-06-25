<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'login',
        'logout',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)

    'allowed_origins' => [
        'http://localhost:3000',          // React dev server
        'http://127.0.0.1:3000',          // Alternative if using 127.0.0.1
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // REQUIRED for cookie-based auth (Sanctum)

];
