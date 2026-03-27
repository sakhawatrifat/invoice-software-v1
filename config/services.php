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
        // Public global airports source used when Travelpayouts airport list is unavailable.
        'airports_dataset_url' => env('AIRPORTS_DATASET_URL', 'https://raw.githubusercontent.com/mwgg/Airports/master/airports.json'),
    ],

    'innotraveltech' => [
        'api_key' => env('INNOTRAVELTECH_API_KEY', 'S5668328683392945113'),
        'secret_code' => env('INNOTRAVELTECH_SECRET_CODE', '2uzKdwNMna7m434jHQd2K2wPmCPJHQ4akuB'),
        'base_url' => env('INNOTRAVELTECH_BASE_URL', 'https://serviceapi.nakamura-tour.com'),
    ],

    /*
    | FlightAPI (flightapi.io) - One-way, Round-trip & Multi-trip flight search
    | Docs: https://docs.flightapi.io/flight-price-api/
    */
    'flightapi' => [
        'api_key' => env('FLIGHTAPI_API_KEY'),
        'region' => env('FLIGHTAPI_REGION', 'US'), // ISO country code for local prices
        'base_url' => env('FLIGHTAPI_BASE_URL', 'https://api.flightapi.io'),
        // Changed/cancelled flight scan: main segments departing within this many calendar days from today (inclusive).
        'upcoming_flight_check_days' => max(1, (int) env('UPCOMMING_FLIGHT_CHECK_DAYS', 2)),
        // After auth/quota-style failures during bulk tracking, skip further API calls for this many minutes (saves credits).
        'bulk_pause_minutes' => max(1, min(60, (int) env('FLIGHTAPI_BULK_PAUSE_MINUTES', 5))),
    ],

    /*
    | WhatsApp Marketing via Twilio (Meta-approved message templates)
    | Docs: https://www.twilio.com/docs/content/send-templates-created-with-the-content-template-builder
    */
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        // If you have optional attachment, use 2 templates:
        // - text-only template: {{1}} (set TWILIO_WHATSAPP_CONTENT_SID_TEXT)
        // - media template: {{1}} + {{2}} (set TWILIO_WHATSAPP_CONTENT_SID_MEDIA)
        // Backward compatible: TWILIO_WHATSAPP_CONTENT_SID behaves like "media" SID.
        'whatsapp_content_sid_text' => env('TWILIO_WHATSAPP_CONTENT_SID_TEXT'),
        'whatsapp_content_sid_media' => env('TWILIO_WHATSAPP_CONTENT_SID_MEDIA', env('TWILIO_WHATSAPP_CONTENT_SID')),
    ],

];
