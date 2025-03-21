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
    'vonage' => [
        'key' => env('VONAGE_API_KEY'),
        'secret' => env('VONAGE_API_SECRET'),
        'sms_from' => env('VONAGE_SMS_FROM'),
        'sms_to' => env('VONAGE_SMS_TO'),
        'message_from' => env('VONAGE_MESSAGE_FROM'),
        'messages_api_url' => 'https://messages-sandbox.nexmo.com/v1/messages'
    ],
    'telegram' =>[
        'token' => env('TELEGRAM_TOKEN'),
        'chat_id' =>env('TELEGRAM_CHAT_ID'),
        'web_url' =>env('TELEGRAM_WEB_URL'),
    ]

];
