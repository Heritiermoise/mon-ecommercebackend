<?php
// Page de diagnostic pour Render
header('Content-Type: application/json');

$checks = [
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'gd' => extension_loaded('gd'),
        'openssl' => extension_loaded('openssl'),
    ],
    'env' => [
        'APP_KEY' => env('APP_KEY') ? 'set' : 'missing',
        'DB_HOST' => env('DB_HOST') ?: 'missing',
        'DB_DATABASE' => env('DB_DATABASE') ?: 'missing',
        'JWT_SECRET' => env('JWT_SECRET') ? 'set' : 'missing',
    ],
    'directories' => [
        'storage' => is_writable(__DIR__ . '/../storage'),
        'bootstrap/cache' => is_writable(__DIR__ . '/../bootstrap/cache'),
    ],
    'timestamp' => date('c'),
];

function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

echo json_encode($checks, JSON_PRETTY_PRINT);