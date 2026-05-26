<?php

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

if (! file_exists($envPath)) {
    fwrite(STDERR, ".env tidak ditemukan.\n");
    exit(1);
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW);

$connection = trim($env['DB_CONNECTION'] ?? 'sqlite', "\"'");

if ($connection === 'sqlite') {
    $database = trim($env['DB_DATABASE'] ?? 'database/database.sqlite', "\"'");
    $path = str_starts_with($database, DIRECTORY_SEPARATOR)
        ? $database
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . $database;

    if (! file_exists($path)) {
        touch($path);
    }

    echo "Database SQLite siap: {$path}\n";
    exit(0);
}

if ($connection !== 'mysql') {
    fwrite(STDERR, "Script ini hanya mendukung sqlite dan mysql. DB_CONNECTION={$connection}\n");
    exit(1);
}

$host = trim($env['DB_HOST'] ?? '127.0.0.1', "\"'");
$port = trim($env['DB_PORT'] ?? '3306', "\"'");
$database = trim($env['DB_DATABASE'] ?? '', "\"'");
$username = trim($env['DB_USERNAME'] ?? 'root', "\"'");
$password = trim($env['DB_PASSWORD'] ?? '', "\"'");

if ($database === '') {
    fwrite(STDERR, "DB_DATABASE kosong di .env.\n");
    exit(1);
}

$pdo = new PDO(
    "mysql:host={$host};port={$port};charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$quotedDatabase = '`' . str_replace('`', '``', $database) . '`';

$pdo->exec("CREATE DATABASE IF NOT EXISTS {$quotedDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "Database MySQL siap: {$database}\n";
