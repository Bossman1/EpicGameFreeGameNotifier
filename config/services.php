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
    'smsApi' => [

        'gatewayapi' => [
            'url' => env('GATEWAYAPI_URL'),
            'token' => env('GATEWAYAPI_TOKEN'),
            'recipients' => env('GATEWAYAPI_RECIPIENTS'),
            'sender' => env('GATEWAYAPI_SENDER'),
            'active' => env('SMS_ALERT','false')
        ]

    ],
    'telegram' => [
        'token' => env('TELEGRAM_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
        'web_url' => env('TELEGRAM_WEB_URL'),
        'active' => env('TELEGRAM_ALERT','false')
    ],
    'email' => [
        'recipients' => env('EMAIL_RECIPIENTS',''),
        'active' => env('EMAIL_ALERT','false')
    ]

];
