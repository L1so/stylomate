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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'fastapi' => [
        // Base URL FastAPI termasuk /api, misalnya http://127.0.0.1:8000/api (kontrak FastAPI final)
        'base_url' => rtrim(env('FASTAPI_BASE_URL', 'http://127.0.0.1:8000/api'), '/'),
        'timeout' => (int) env('FASTAPI_TIMEOUT', 10),
        // Timeout request AI (Try-On generate), harus lebih besar dari AI_TIMEOUT_SECONDS FastAPI (default 300)
        'ai_timeout' => (int) env('FASTAPI_AI_TIMEOUT', 330),
        'use_mock' => filter_var(env('FASTAPI_USE_MOCK', false), FILTER_VALIDATE_BOOL),
    ],

];
