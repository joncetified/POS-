<?php

return [
    'name' => env('STORE_NAME'),
    'cashier' => env('STORE_CASHIER', 'Barista 01'),
    'address' => env('STORE_ADDRESS', 'Jl. Kopi Nusantara No. 8, Jakarta'),
    'manager' => env('STORE_MANAGER', 'Manager Operasional'),
    'contact_email' => env('STORE_CONTACT_EMAIL'),
    'contact_phone' => env('STORE_CONTACT_PHONE'),
    'contact_whatsapp' => env('STORE_CONTACT_WHATSAPP'),
    'table_count' => env('STORE_TABLE_COUNT', 20),
];
