<?php
/**
 * Debug script to check admin account status
 * This will help diagnose login issues
 */

define('SECURE_ACCESS', true);
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Account Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Admin Account Debug Information</h1>
    
    <?php
    try {
        $pdo = getPdoConnection();
        
        if (!$pdo) {
            echo '<div class="section error"><strong>ERROR:</strong> Cannot connect to database!</div>';
            exit;
        }
        
        echo '<div class="section success"><strong>✓</strong> Database connection successful</div>';
        
        // Check if admin user exists
        $stmt = $pdo->prepare('SELECT id, username, email, role, full_name, organization, status, created_at, LENGTH(password_hash) as pwd_len FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => 'admin']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo '<div class="section error"><strong>ERROR:</strong> Admin user not found in database!</div>';
            echo '<div class="section"><p>Run <a href="create-admin.php">create-admin.php</a> to create the admin account.</p></div>';
        } else {
            echo '<div class="section success"><strong>✓</strong> Admin user found</div>';
            echo '<div class="section">';
            echo '<h3>Admin Account Details:</h3>';
            echo '<pre>';
            print_r($admin);
            echo '</pre>';
            
            // Check status
            $status = $admin['status'] ?? 'unknown';
            if ($status === 'approved' || $status === 'active') {
                echo '<p class="success"><strong>✓ Status:</strong> ' . htmlspecialchars($status) . ' (can login)</p>';
            } else {
                echo '<p class="error"><strong>✗ Status:</strong> ' . htmlspecialchars($status) . ' (cannot login - needs to be "approved" or "active")</p>';
            }
            
            // Check password hash
            if (empty($admin['pwd_len']) || $admin['pwd_len'] < 60) {
                echo '<p class="error"><strong>✗ Password:</strong> Invalid or missing password hash</p>';
            } else {
                echo '<p class="success"><strong>✓ Password:</strong> Hash exists (' . $admin['pwd_len'] . ' characters)</p>';
            }
            
            // Test password verification
            echo '<h3>Password Verification Test:</h3>';
            $test_passwords = ['admin123', 'admin', 'password'];
            foreach ($test_passwords as $test_pwd) {
                $stmt2 = $pdo->prepare('SELECT password_hash FROM users WHERE username = :username LIMIT 1');
                $stmt2->execute([':username' => 'admin']);
                $pwd_row = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($pwd_row && password_verify($test_pwd, $pwd_row['password_hash'])) {
                    echo '<p class="success"><strong>✓</strong> Password "' . htmlspecialchars($test_pwd) . '" is CORRECT!</p>';
                    break;
                } else {
                    echo '<p class="error"><strong>✗</strong> Password "' . htmlspecialchars($test_pwd) . '" is incorrect</p>';
                }
            }
            
            echo '</div>';
        }
        
        // Show all users
        echo '<div class="section">';
        echo '<h3>All Users in Database:</h3>';
        $allUsers = $pdo->query('SELECT id, username, email, role, status, created_at FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        if (empty($allUsers)) {
            echo '<p>No users found.</p>';
        } else {
            echo '<pre>';
            print_r($allUsers);
            echo '</pre>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section error">';
        echo '<strong>ERROR:</strong> ' . htmlspecialchars($e->getMessage());
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>
    
    <div class="section">
        <h3>Actions:</h3>
        <p><a href="create-admin.php">Create/Recreate Admin Account</a></p>
        <p><a href="login.php">Go to Login Page</a></p>
    </div>
</body>
</html>

