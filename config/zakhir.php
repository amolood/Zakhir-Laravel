<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zakhir Environment
    |--------------------------------------------------------------------------
    |
    | Determines which set of credentials the package uses.
    | Supported: "production", "staging"
    |
    */
    'environment' => 'production',

    /*
    |--------------------------------------------------------------------------
    | Production Credentials
    |--------------------------------------------------------------------------
    |
    | Obtain these from your Zakhir merchant dashboard at https://zakhir.cloud
    |
    */
    'base_url' => 'https://zakhir.cloud/api/',
    'tenant'   => '',
    'profile'  => '',
    'api_key'  => '',

    /*
    |--------------------------------------------------------------------------
    | Staging Credentials
    |--------------------------------------------------------------------------
    |
    | Used automatically when 'environment' is set to "staging".
    |
    */
    'staging_base_url' => '',
    'staging_tenant'   => '',
    'staging_profile'  => '',
    'staging_api_key'  => '',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | webhook_url — The publicly accessible URL where Zakhir will POST payment
    |               status notifications. Must be reachable by Zakhir's servers.
    |
    | return_url  — Where customers are redirected after completing or
    |               cancelling checkout on the Zakhir-hosted page.
    |
    */
    'webhook_url' => '',
    'return_url'  => '',

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret  (optional — recommended in production)
    |--------------------------------------------------------------------------
    |
    | When set, every incoming webhook is verified using HMAC-SHA256 against
    | the X-Zakhir-Signature header. Leave empty to skip signature checks.
    |
    */
    'webhook_secret' => '',

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum seconds to wait for a response from the Zakhir API.
    |
    */
    'timeout' => 15,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, every outgoing API request and its response are written to
    | the zakhir_logs table for auditing and debugging.
    |
    */
    'logging' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | enabled    — Register the built-in webhook route automatically.
    |              Set to false if you want to define the route yourself.
    |
    | prefix     — URL prefix for the webhook route.
    |              Default: POST /api/zakhir/webhook
    |
    | middleware — Middleware stack applied to the webhook route.
    |
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'api/zakhir',
        'middleware' => ['api'],
    ],

];
