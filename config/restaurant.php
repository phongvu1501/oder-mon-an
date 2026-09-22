<?php

return [
    'name' => env('RESTAURANT_NAME', 'OderMonAn'),
    'pagination' => (int) env('RESTAURANT_PAGINATION', 10),
    'inventory' => [
        'allow_negative_stock' => (bool) env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),
        'stock_deduction_order_status' => env('INVENTORY_DEDUCTION_ORDER_STATUS', 'preparing'),
        'auto_create_stock_transaction' => (bool) env('INVENTORY_AUTO_TRANSACTION', true),
        'enable_batch_tracking' => (bool) env('INVENTORY_BATCH_TRACKING', true),
        'enable_expiry_tracking' => (bool) env('INVENTORY_EXPIRY_TRACKING', true),
        'cost_method' => env('INVENTORY_COST_METHOD', 'weighted_average'),
        'unmanaged_product_policy' => env('INVENTORY_UNMANAGED_PRODUCT_POLICY', 'warn'),
        'near_expiry_days' => (int) env('INVENTORY_NEAR_EXPIRY_DAYS', 7),
        'default_warehouse_code' => env('INVENTORY_DEFAULT_WAREHOUSE', 'MAIN'),
    ],
    'bank' => [
        'code' => env('PAYMENT_BANK_CODE'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME'),
    ],
];
