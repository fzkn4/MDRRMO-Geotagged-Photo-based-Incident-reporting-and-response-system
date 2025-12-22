<?php
/**
 * Script to create initial admin account
 * Run this once to set up the first admin user
 * 
 * Usage: Visit http://localhost/create-admin.php in your browser
 * Or run: php create-admin.php (if PHP CLI is available)
 */

define('SECURE_ACCESS', true);
require_once 'config.php';
require_once 'auth.php';

// Check if running in CLI or web browser
$is_cli = php_sapi_name() === 'cli';

// Default admin credentials
$admin_username = 'administrator';
$admin_password = 'admin123'; // CHANGE THIS AFTER FIRST LOGIN!
$admin_email = 'admin@mdrrmo.local';
$admin_full_name = 'System Administrator';
$admin_organization = 'MDRRMO';

// Handle form submission (if accessed via browser)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_cli) {
    $admin_username = trim($_POST['username'] ?? $admin_username);
    $admin_password = $_POST['password'] ?? $admin_password;
    $admin_email = trim($_POST['email'] ?? $admin_email);
    $admin_full_name = trim($_POST['full_name'] ?? $admin_full_name);
    $admin_organization = trim($_POST['organization'] ?? $admin_organization);
}

// Check if admin already exists
if (userExists($admin_username)) {
    if ($is_cli) {
        echo "Admin user already exists!\n";
        echo "Username: $admin_username\n";
    } else {
        echo "<!DOCTYPE html><html><head><title>Admin Setup</title></head><body>";
        echo "<h2>Admin user already exists!</h2>";
        echo "<p>Username: <strong>$admin_username</strong></p>";
        echo "<p><a href='login.php'>Go to Login</a></p>";
        echo "</body></html>";
    }
    exit(0);
}

// Create password hash
$password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
if (strlen($password_hash) < 60) {
    if ($is_cli) {
        echo "✗ ERROR: Password hash generation failed or hash is too short!\n";
    } else {
        echo "<!DOCTYPE html><html><head><title>Admin Setup - Error</title></head><body>";
        echo "<h2>Error: Password hash generation failed</h2>";
        echo "<p>Hash length: " . strlen($password_hash) . " (should be at least 60)</p>";
        echo "</body></html>";
    }
    exit(1);
}

// Create admin user with approved status
$admin_data = [
    'username' => $admin_username,
    'email' => $admin_email,
    'password' => $password_hash,
    'full_name' => $admin_full_name,
    'role' => 'admin',
    'organization' => $admin_organization,
    'status' => 'approved' // Auto-approved for initial admin
];

if (createUser($admin_data)) {
    if ($is_cli) {
        echo "✓ Admin account created successfully!\n\n";
        echo "Login credentials:\n";
        echo "  Username: $admin_username\n";
        echo "  Password: $admin_password\n\n";
        echo "⚠️  IMPORTANT: Please change the password after first login!\n";
        echo "⚠️  You can delete this file (create-admin.php) after setup.\n";
    } else {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Setup - Success</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #f5f5f5; padding: 50px 0; }
        .container { max-width: 600px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card shadow'>
            <div class='card-body p-5'>
                <div class='text-center mb-4'>
                    <div class='bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center' style='width: 80px; height: 80px;'>
                        <svg xmlns='http://www.w3.org/2000/svg' width='40' height='40' fill='currentColor' class='text-success' viewBox='0 0 16 16'>
                            <path d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061l3.5 3.5a.75.75 0 0 0 1.07-.01l4.5-5.5a.75.75 0 0 0-.022-1.08z'/>
                        </svg>
                    </div>
                </div>
                <h2 class='text-center mb-4'>Admin Account Created!</h2>
                <div class='alert alert-info'>
                    <strong>Login Credentials:</strong><br>
                    Username: <code>$admin_username</code><br>
                    Password: <code>$admin_password</code>
                </div>
                <div class='alert alert-warning'>
                    <strong>⚠️ Important:</strong> Please change the password after first login!
                </div>
                <div class='d-grid gap-2'>
                    <a href='login.php' class='btn btn-primary btn-lg'>Go to Login</a>
                    <small class='text-muted text-center mt-2'>You can delete this file (create-admin.php) after setup.</small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    }
} else {
    if ($is_cli) {
        echo "✗ Failed to create admin account. Please check database connection.\n";
    } else {
        echo "<!DOCTYPE html><html><head><title>Admin Setup - Error</title></head><body>";
        echo "<h2>Failed to create admin account</h2>";
        echo "<p>Please check database connection and try again.</p>";
        echo "</body></html>";
    }
    exit(1);
}

