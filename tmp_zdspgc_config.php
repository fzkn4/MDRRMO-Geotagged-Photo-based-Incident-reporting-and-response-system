<?php

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'zdspgc_db';
$dbUser = getenv('DB_USERNAME') ?: 'zdspgc_user';
$dbPass = getenv('DB_PASSWORD') ?: 'zdspgc_pass';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

$retries = 10;
$sleepSeconds = 1;
$lastError = null;
while ($retries > 0) {
    try {
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $lastError = null;
        break;
    } catch (PDOException $e) {
        $lastError = $e;
        $retries--;
        sleep($sleepSeconds);
    }
}

if (!isset($pdo)) {
    http_response_code(500);
    if ((getenv('APP_ENV') ?: '') === 'development') {
        die('Database connection failed: ' . ($lastError ? $lastError->getMessage() : 'unknown error'));
    }
    die('Database connection failed.');
}


