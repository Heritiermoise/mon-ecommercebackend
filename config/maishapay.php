<?php

return [
    'env' => env('MAISHAPAY_ENV', 'sandbox'),
    'merchant_id' => env('MAISHAPAY_MERCHANT_ID'),
    'public_key' => env('MAISHAPAY_PUBLIC_KEY'),
    'secret_key' => env('MAISHAPAY_SECRET_KEY'),
    'base_url' => env('MAISHAPAY_BASE_URL', 'https://sandbox.maishapay.com/api/v1'),
    
    'urls' => [
        'sandbox' => 'https://sandbox.maishapay.com/api/v1',
        'production' => 'https://api.maishapay.com/api/v1',
    ],
    
    'webhook_secret' => env('MAISHAPAY_WEBHOOK_SECRET'),
    
    'timeout' => 30,
];