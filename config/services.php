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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'EUR'),
        'payment_methods' => array_filter(array_map('trim', explode(',', env('STRIPE_PAYMENT_METHODS', 'card')))),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'webhook_secret' => env('BREVO_WEBHOOK_SECRET'),
        'sender_email' => env('BREVO_SENDER_EMAIL'),
        'sender_name' => env('BREVO_SENDER_NAME', env('APP_NAME')),
        'abandoned_cart_template_id' => env('BREVO_ABANDONED_CART_TEMPLATE_ID'),
        'order_confirmation_template_id' => env('BREVO_ORDER_CONFIRMATION_TEMPLATE_ID'),
        'tracking_update_template_id' => env('BREVO_TRACKING_UPDATE_TEMPLATE_ID'),
        'quote_request_admin_template_id' => env('BREVO_QUOTE_REQUEST_ADMIN_TEMPLATE_ID'),
        'quote_request_customer_template_id' => env('BREVO_QUOTE_REQUEST_CUSTOMER_TEMPLATE_ID'),
        'quote_request_recipient_email' => env('QUOTE_REQUEST_RECIPIENT_EMAIL', 'infoprintaqui@gmail.com'),
        'default_list_id' => env('BREVO_DEFAULT_LIST_ID'),
    ],

];
