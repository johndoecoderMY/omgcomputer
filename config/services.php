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

    'cpanel' => [
        'host' => env('CPANEL_HOST'),
        'port' => (int) env('CPANEL_PORT', 2083),
        'username' => env('CPANEL_USERNAME'),
        'api_token' => env('CPANEL_API_TOKEN'),
        'email_domain' => env('CPANEL_EMAIL_DOMAIN'),
        'email_quota' => (int) env('CPANEL_EMAIL_QUOTA', 1024),
        'enabled' => filled(env('CPANEL_HOST'))
            && filled(env('CPANEL_USERNAME'))
            && filled(env('CPANEL_API_TOKEN'))
            && filled(env('CPANEL_EMAIL_DOMAIN')),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'ad_account_id' => env('META_AD_ACCOUNT_ID'),
        'page_id' => env('META_PAGE_ID'),
        'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
        'instagram_user_id' => env('META_INSTAGRAM_USER_ID'),
        'instagram_access_token' => env('META_INSTAGRAM_ACCESS_TOKEN'),
        'public_media_base_url' => env('META_PUBLIC_MEDIA_BASE_URL'),
        'whatsapp_business_account_id' => env('META_WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'whatsapp_phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'graph_api_version' => env('META_GRAPH_API_VERSION', 'v25.0'),
    ],

    'delivery' => [
        'provider' => env('EASYPARCEL_PROVIDER', 'easyparcel'),
        'authentication' => env('EASYPARCEL_AUTHENTICATION'),
        'api_key' => env('EASYPARCEL_API_KEY'),
        'api_url' => env('EASYPARCEL_API_URL'),
        'environment' => env('EASYPARCEL_ENVIRONMENT', 'sandbox'),
    ],

    'marketplaces' => [
        'shopee' => [
            'partner_id' => env('SHOPEE_PARTNER_ID'),
            'partner_key' => env('SHOPEE_PARTNER_KEY'),
            'redirect_url' => env('SHOPEE_REDIRECT_URL'),
        ],
        'lazada' => [
            'app_key' => env('LAZADA_APP_KEY'),
            'app_secret' => env('LAZADA_APP_SECRET'),
            'redirect_url' => env('LAZADA_REDIRECT_URL'),
            'api_url' => env('LAZADA_API_URL', 'https://api.lazada.com.my/rest'),
            'auth_url' => env('LAZADA_AUTH_URL', 'https://auth.lazada.com/rest'),
        ],
    ],

];
