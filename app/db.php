<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = $GLOBALS['MAWARID_CONFIG'];
    $driver = $cfg['driver'] ?? 'sqlite';

    if ($driver === 'mysql') {
        $m = $cfg['mysql'];
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $m['host'],
            $m['name'],
            $m['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $m['user'], $m['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET NAMES utf8mb4");
        return $pdo;
    }

    $path = $cfg['sqlite_path'];
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function db_driver(): string
{
    return $GLOBALS['MAWARID_CONFIG']['driver'] ?? 'sqlite';
}

function table_exists(string $name): bool
{
    $pdo = db();
    if (db_driver() === 'mysql') {
        $st = $pdo->prepare('SHOW TABLES LIKE ?');
        $st->execute([$name]);
        return (bool) $st->fetchColumn();
    }
    $st = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $st->execute([$name]);
    return (bool) $st->fetchColumn();
}
