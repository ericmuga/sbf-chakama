<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Daraja API Configuration
    |--------------------------------------------------------------------------
    | Set MPESA_ENV=production in .env for live transactions.
    | Sandbox credentials from https://developer.safaricom.co.ke/
    |
    | Required .env variables:
    |   MPESA_ENV=sandbox
    |   MPESA_CONSUMER_KEY=your_consumer_key
    |   MPESA_CONSUMER_SECRET=your_consumer_secret
    |   MPESA_BUSINESS_SHORT_CODE=174379         (sandbox paybill/till)
    |   MPESA_PASS_KEY=your_passkey
    |   MPESA_CALLBACK_URL=https://yourdomain.com  (must be HTTPS)
    |   MPESA_TRANSACTION_TYPE=CustomerPayBillOnline  (or CustomerBuyGoodsOnline)
    */

    'env' => env('MPESA_ENV', 'sandbox'),

    'consumer_key' => env('MPESA_CONSUMER_KEY'),

    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),

    'shortcode' => env('MPESA_BUSINESS_SHORT_CODE', '174379'),

    'passkey' => env('MPESA_PASS_KEY'),

    'callback_url' => env('MPESA_CALLBACK_URL', env('APP_URL')),

    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
];
