<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],   // السماح بكل الوسائل HTTP: GET, POST, PUT, DELETE, OPTIONS

    'allowed_origins' => [
        'http://localhost:5173',     // أو البورت الذي تستخدمه للـ Vue dev server
        'http://127.0.0.1:5173',
        // يمكنك إضافة دومينات الإنتاج لاحقًا
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],   // أو حدد فقط الحقول التي تحتاجها مثل ['Content-Type', 'Authorization']

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,   // لأنك تستخدم توكن Bearer، وليس authentication باستخدام الكوكيز
];
