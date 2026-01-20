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

    'travelpayouts' => [
        'token' => env('TRAVELPAYOUTS_TOKEN'),
        'marker' => env('TRAVELPAYOUTS_MARKER'),
        'website_url' => env('TRAVELPAYOUTS_WEBSITE_URL', env('APP_URL')),
        'use_new_api' => env('TRAVELPAYOUTS_USE_NEW_API', false), // Set to true when new API is approved
    ],

    'innotraveltech' => [
        'api_key' => env('INNOTRAVELTECH_API_KEY', 'S5668328683392945113'),
        'secret_code' => env('INNOTRAVELTECH_SECRET_CODE', '2uzKdwNMna7m434jHQd2K2wPmCPJHQ4akuB'),
        'base_url' => env('INNOTRAVELTECH_BASE_URL', 'https://serviceapi.nakamura-tour.com'),
    ],

];
