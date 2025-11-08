<?php

declare(strict_types=1);

// Database configuration and PDO connection helper

if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

/**
 * Attempt to load a simple KEY=VALUE .env file if present.
 */
function loadEnvFile(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    $envPath = __DIR__ . '/.env';

    if (!is_readable($envPath)) {
        $loaded = true;
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $delimiterPos = strpos($line, '=');

        if ($delimiterPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $delimiterPos));
        $value = trim(substr($line, $delimiterPos + 1));

        if ($key === '' || array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER)) {
            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }

    $loaded = true;
}

/**
 * Resolve database configuration using environment variables with sensible defaults.
 */
function getDatabaseConfig(): array
{
    loadEnvFile();

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';

    // Support both DB_DATABASE/DB_NAME and DB_USERNAME/DB_USER naming conventions.
    $dbname = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'geotagged');
    $username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'geotagged_user');
    $password = getenv('DB_PASSWORD') ?: 'geotagged_password';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    return [
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'username' => $username,
        'password' => $password,
        'options' => $options,
    ];
}

/**
 * Get a shared PDO connection instance with retry support.
 */
function getPdoConnection(): ?PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = getDatabaseConfig();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );

    $maxAttempts = (int) (getenv('DB_CONNECTION_RETRIES') ?: 5);
    $maxAttempts = max(1, $maxAttempts);
    $sleepSeconds = (int) (getenv('DB_CONNECTION_SLEEP') ?: 1);
    $lastError = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            return $pdo;
        } catch (PDOException $exception) {
            $lastError = $exception;
            error_log(sprintf(
                'Database connection attempt %d/%d failed: %s',
                $attempt,
                $maxAttempts,
                $exception->getMessage()
            ));

            if ($attempt < $maxAttempts) {
                sleep($sleepSeconds);
            }
        }
    }

    if ($lastError instanceof PDOException) {
        error_log(sprintf(
            'Database connection failed after %d attempts (host=%s, port=%s, db=%s, user=%s)',
            $maxAttempts,
            $config['host'],
            $config['port'],
            $config['database'],
            $config['username']
        ));
    }

    if ((getenv('APP_ENV') ?: '') === 'development' && $lastError instanceof PDOException) {
        throw $lastError;
    }

    return null;
}

// Intentionally no closing PHP tag to prevent accidental output
