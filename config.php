<?php
// Database configuration and PDO connection helper

if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

function getPdoConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'geotagged';
    $username = getenv('DB_USER') ?: 'geotagged_user';
    $password = getenv('DB_PASSWORD') ?: 'geotagged_password';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
    } catch (Throwable $e) {
        // In development, surface a concise message
        http_response_code(500);
        die('Database connection failed.');
    }

    return $pdo;
}

// Intentionally no closing PHP tag to prevent accidental output
