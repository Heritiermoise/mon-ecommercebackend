<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'maishapay' => [
        'merchant_id' => env('MAISHAPAY_MERCHANT_ID'),
        'env' => env('MAISHAPAY_ENV', 'sandbox'),
        'api_url' => env('MAISHAPAY_API_URL', 'https://marchand.maishapay.online'),
        'checkout_url' => env('MAISHAPAY_CHECKOUT_URL', 'https://marchand.maishapay.online/payment/vers1.0/merchant/checkout'),
        'public_key' => env('MAISHAPAY_PUBLIC_KEY'),
        'secret_key' => env('MAISHAPAY_SECRET_KEY'),
    ],
];