<?php
/**
 * إعدادات موارد.
 * للرفع على استضافة MySQL غيّر driver إلى mysql واملأ بيانات القاعدة.
 */
return [
    'app_name' => 'موارد',
    'driver' => 'sqlite', // sqlite | mysql
    'sqlite_path' => __DIR__ . '/storage/mawarid.sqlite',
    'mysql' => [
        'host' => 'localhost',
        'name' => 'mawarid',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
];
