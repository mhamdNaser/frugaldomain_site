<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hostinger' => [
        // Personal Access Token for https://developers.hostinger.com.
        // Never commit a real value - it belongs in .env only.
        'token' => env('HOSTINGER_API_TOKEN'),

        // The hosting domain subdomains are created under. Resolved from the
        // API at runtime when possible; this is the fallback / filter.
        'domain' => env('HOSTINGER_DOMAIN', 'frugaldomain.site'),

        'base_url' => env('HOSTINGER_API_BASE_URL', 'https://developers.hostinger.com'),

        // Seconds. Hostinger is rate limited to 90 req/min, so keep requests short.
        'timeout' => (int) env('HOSTINGER_API_TIMEOUT', 20),
    ],

];
