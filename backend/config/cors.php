<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The frontend (Nuxt) and backend (Laravel) run on separate origins, so the
    | API must explicitly allow the frontend origin for the `/api/*` paths.
    | Authentication is token-based (bearer tokens via Sanctum), so cookies and
    | credential support are not required.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // The browser reaches the API from the host (FRONTEND_URL) in normal dev,
    // and from inside the Compose network (FRONTEND_URL_INTERNAL) when the
    // Playwright `e2e` service drives the frontend. Both origins are allowed so
    // E2E works without per-environment juggling (mirrors the frontend's
    // public/internal API base split — see docs/architecture.md).
    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('FRONTEND_URL_INTERNAL', 'http://frontend:3000'),
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
