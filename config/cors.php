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

    // 1. Buka semua path termasuk endpoint login/register jika tidak diawali /api
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'], 

    // 2. Sebutkan domain Vercel & lokal secara spesifik (JANGAN pakai bintang '*' jika pakai Bearer token)
    'allowed_origins' => [
        'https://smartspend-fe.vercel.app', 
        'http://localhost:5173'
    ], 

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'allowed_methods' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 3. Set ke true agar browser mengizinkan pengiriman header Authorization & Cookie lintas domain
    'supports_credentials' => true, 
];
Route::get('/clear-api-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    return response()->json(['message' => 'Semua cache di Railway berhasil dibersihkan!']);
});