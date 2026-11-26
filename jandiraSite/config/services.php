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

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'number' => env('WHATSAPP_NUMBER'),
    ],

    'infinitepay' => [
        'driver' => env('INFINITEPAY_DRIVER', 'mock'),
        'base_url' => rtrim((string) env('INFINITEPAY_BASE_URL', 'https://api.checkout.infinitepay.io'), '/'),
        'handle' => ltrim(trim((string) env('INFINITEPAY_HANDLE', '')), '$'),
        'api_key' => env('INFINITEPAY_API_KEY'),
        'webhook_secret' => env('INFINITEPAY_WEBHOOK_SECRET'),
        'timeout' => (int) env('INFINITEPAY_TIMEOUT', 15),
        'single_payment' => filter_var(env('INFINITEPAY_SINGLE_PAYMENT', true), FILTER_VALIDATE_BOOL),
        'require_delivery_address' => filter_var(env('INFINITEPAY_REQUIRE_DELIVERY_ADDRESS', false), FILTER_VALIDATE_BOOL),
    ],

];
