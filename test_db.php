<?php
/**
 * Quick database connection test
 * Access this file to test database connectivity
 */
define('SECURE_ACCESS', true);
require_once 'config.php';

header('Content-Type: text/plain');

echo "=== Database Connection Test ===\n\n";

// Show environment variables
echo "Environment Variables:\n";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'localhost (default)') . "\n";
echo "DB_PORT: " . (getenv('DB_PORT') ?: '3306 (default)') . "\n";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'geotagged (default)') . "\n";
echo "DB_USER: " . (getenv('DB_USER') ?: 'geotagged_user (default)') . "\n";
echo "DB_PASSWORD: " . (getenv('DB_PASSWORD') ? '***set***' : 'geotagged_password (default)') . "\n\n";

// Test connection
echo "Testing connection...\n";
$pdo = getPdoConnection();

if ($pdo) {
    echo "✓ Connection successful!\n\n";
    
    // Test query
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Users in database: " . $result['count'] . "\n\n";
        
        // List users (without passwords)
        $stmt = $pdo->query("SELECT id, username, email, role, 
                            CASE 
                                WHEN password_hash IS NULL THEN 'NULL'
                                WHEN password_hash = '' THEN 'EMPTY'
                                ELSE 'SET (' || LENGTH(password_hash) || ' chars)'
                            END as pwd_status
                            FROM users ORDER BY id");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            echo "User list:\n";
            foreach ($users as $user) {
                echo "  - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}, Password: {$user['pwd_status']}\n";
            }
        } else {
            echo "No users found.\n";
        }
    } catch (Exception $e) {
        echo "✗ Error querying database: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Connection failed!\n";
    echo "Check your docker-compose.yml environment variables.\n";
    echo "Make sure the database container is running.\n";
}

echo "\n=== End Test ===\n";

