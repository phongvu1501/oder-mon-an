<?php

return [
    'name' => env('RESTAURANT_NAME', 'OderMonAn'),
    'pagination' => (int) env('RESTAURANT_PAGINATION', 15),
    'bank' => [
        'code' => env('PAYMENT_BANK_CODE'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME'),
    ],
];
