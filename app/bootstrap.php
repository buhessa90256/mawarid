<?php

declare(strict_types=1);

$GLOBALS['MAWARID_CONFIG'] = require dirname(__DIR__) . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function app_installed(): bool
{
    try {
        return table_exists('users');
    } catch (Throwable $e) {
        return false;
    }
}

function require_installed(): void
{
    if (!app_installed()) {
        redirect('install.php');
    }
}
