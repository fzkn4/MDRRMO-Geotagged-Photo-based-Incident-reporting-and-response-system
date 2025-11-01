<?php
/**
 * Debug script to help diagnose login issues
 * Remove this file in production!
 */
define('SECURE_ACCESS', true);
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Debug Information</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 3px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>Login Debug Information</h1>
    
    <div class="section">
        <h2>1. Database Connection Test</h2>
        <?php
        $pdo = getPdoConnection();
        if ($pdo) {
            echo '<p class="success">✓ Database connection successful</p>';
            echo '<pre>';
            echo "Host: " . getenv('DB_HOST') ?: 'localhost' . "\n";
            echo "Port: " . getenv('DB_PORT') ?: '3306' . "\n";
            echo "Database: " . getenv('DB_NAME') ?: 'geotagged' . "\n";
            echo "User: " . getenv('DB_USER') ?: 'geotagged_user' . "\n";
            echo "</pre>";
        } else {
            echo '<p class="error">✗ Database connection failed</p>';
            echo '<p>Check your environment variables and Docker configuration.</p>';
        }
        ?>
    </div>
    
    <?php if ($pdo): ?>
    <div class="section">
        <h2>2. Users Table Check</h2>
        <?php
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                echo '<p class="success">✓ Users table exists</p>';
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo '<h3>Table Structure:</h3>';
                echo '<table>';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="error">✗ Users table does not exist</p>';
                echo '<p>Run the database initialization script: db/init.sql</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Error checking table: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. Users in Database</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT id, username, email, role, created_at, 
                                  LENGTH(password_hash) as pwd_len,
                                  CASE 
                                    WHEN password_hash IS NULL THEN 'NULL'
                                    WHEN password_hash = '' THEN 'EMPTY'
                                    ELSE 'SET'
                                  END as pwd_status
                                  FROM users ORDER BY id");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo '<p class="success">✓ Found ' . count($users) . ' user(s) in database</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Password Status</th><th>Created</th></tr>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($user['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['username']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                    $pwdStatus = $user['pwd_status'];
                    $pwdClass = ($pwdStatus === 'SET') ? 'success' : 'error';
                    echo '<td class="' . $pwdClass . '">' . htmlspecialchars($pwdStatus) . ' (' . $user['pwd_len'] . ' chars)</td>';
                    echo '<td>' . htmlspecialchars($user['created_at']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">⚠ No users found in database</p>';
                echo '<p>Register a user first via signup.php</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Error fetching users: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Test Login Function</h2>
        <?php
        if (isset($_GET['test_username']) && isset($_GET['test_password'])) {
            $test_username = $_GET['test_username'];
            $test_password = $_GET['test_password'];
            
            echo '<h3>Testing login for: ' . htmlspecialchars($test_username) . '</h3>';
            
            // First check if user exists
            $userExists = userExists($test_username);
            echo '<p>User exists check: ' . ($userExists ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . '</p>';
            
            // Try to get user from database
            try {
                $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role FROM users WHERE username = :u OR email = :u LIMIT 1');
                $stmt->execute([':u' => $test_username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo '<p class="success">✓ User found in database</p>';
                    echo '<pre>';
                    echo "ID: " . $user['id'] . "\n";
                    echo "Username: " . $user['username'] . "\n";
                    echo "Email: " . $user['email'] . "\n";
                    echo "Role: " . $user['role'] . "\n";
                    echo "Password hash exists: " . (!empty($user['password_hash']) ? 'Yes (' . strlen($user['password_hash']) . ' chars)' : 'No') . "\n";
                    echo "</pre>";
                    
                    if (!empty($user['password_hash'])) {
                        $verify_result = password_verify($test_password, $user['password_hash']);
                        echo '<p>Password verification: ' . ($verify_result ? '<span class="success">✓ Match</span>' : '<span class="error">✗ No match</span>') . '</p>';
                        
                        if ($verify_result) {
                            $auth_result = authenticateUser($test_username, $test_password);
                            echo '<p>authenticateUser() result: ' . ($auth_result ? '<span class="success">✓ Success</span>' : '<span class="error">✗ Failed</span>') . '</p>';
                        }
                    } else {
                        echo '<p class="error">✗ Password hash is empty in database</p>';
                    }
                } else {
                    echo '<p class="error">✗ User not found in database</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p>Test a login by adding parameters: ?test_username=YOUR_USERNAME&test_password=YOUR_PASSWORD</p>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>5. PHP Error Log Location</h2>
        <p>Check PHP error logs for detailed error messages. Common locations:</p>
        <ul>
            <li>Docker container: Check container logs with <code>docker logs geotagged-incident-reporting</code></li>
            <li>PHP error log: <?php echo ini_get('error_log') ?: 'Not configured'; ?></li>
            <li>Apache error log: Usually in /var/log/apache2/error.log (inside container)</li>
        </ul>
    </div>
    
    <?php endif; ?>
    
    <div class="section">
        <h2>6. Environment Variables</h2>
        <pre>
DB_HOST: <?php echo getenv('DB_HOST') ?: 'localhost (default)'; ?>
DB_PORT: <?php echo getenv('DB_PORT') ?: '3306 (default)'; ?>
DB_NAME: <?php echo getenv('DB_NAME') ?: 'geotagged (default)'; ?>
DB_USER: <?php echo getenv('DB_USER') ?: 'geotagged_user (default)'; ?>
DB_PASSWORD: <?php echo getenv('DB_PASSWORD') ? '***' . str_repeat('*', min(10, strlen(getenv('DB_PASSWORD')) - 3)) : 'geotagged_password (default)'; ?>
        </pre>
        <p class="warning">⚠ Make sure these match your docker-compose.yml environment variables</p>
    </div>
    
    <hr>
    <p><a href="login.php">← Back to Login</a> | <a href="signup.php">Go to Signup</a></p>
    <p style="color: red; font-weight: bold;">⚠ REMOVE THIS FILE IN PRODUCTION!</p>
</body>
</html>

