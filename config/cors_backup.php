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

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://localhost:3000', 
        'http://127.0.0.1:3000',
        'http://localhost:4173',
        'http://127.0.0.1:4173',
        'https://m-martplus.com',
        'https://www.m-martplus.com',
        'https://mmartplus-frontend-private.vercel.app',
        'https://mmartplus-frontend-private-tayooshoboke6s-projects.vercel.app',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['X-CSRF-TOKEN', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization', 'X-XSRF-TOKEN', 'Origin', 'Content-Length'],

    'exposed_headers' => ['Cache-Control', 'Content-Language', 'Content-Type', 'Expires', 'Last-Modified', 'Pragma'],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
