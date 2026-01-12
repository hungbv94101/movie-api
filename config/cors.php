<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'graphql', 'graphql/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => env('APP_ENV') === 'production' ? [
        // Add your production domains here when deploying
        // 'https://yourdomain.com',
        // 'https://www.yourdomain.com',
    ] : [
        'http://localhost:5173',     // Vite dev server
        'http://localhost:3000',     // React dev server alternative
        'http://127.0.0.1:5173',     // Local IP Vite
        'http://127.0.0.1:3000',     // Local IP React
        'http://localhost:8080',     // Docker frontend (if needed)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];