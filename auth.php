<?php
// Authentication helper functions

// Prevent direct access to this file
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Start session as early as possible to avoid "headers already sent"
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disabled for production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database config
require_once __DIR__ . '/config.php';

function checkLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function logout() {
    startSession();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit();
}

function getCurrentUser() {
    return $_SESSION['username'] ?? 'User';
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'client';
}

function getUserData() {
    return $_SESSION['user_data'] ?? null;
}

// File-based user storage (replace with database in production)
function getUsersFile() {
    return 'users.json';
}

function loadUsers() {
    $file = getUsersFile();
    if (file_exists($file)) {
        $data = file_get_contents($file);
        return json_decode($data, true) ?: [];
    }
    return [];
}

function saveUsers($users) {
    $file = getUsersFile();
    return file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

function userExists($username) {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->execute([':u' => $username]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function emailExists($email) {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->execute([':e' => $email]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function createUser($user_data) {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        
        // Validate password hash before inserting
        if (empty($user_data['password']) || strlen($user_data['password']) < 60) {
            return false;
        }
        
        // Default status to 'pending' if not provided, unless it's explicitly set
        $status = $user_data['status'] ?? 'pending';
        
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, full_name, organization, phone, status) VALUES (:username, :email, :password_hash, :role, :full_name, :organization, :phone, :status)');
        if (!$stmt) {
            return false;
        }
        
        $result = $stmt->execute([
            ':username' => $user_data['username'],
            ':email' => $user_data['email'],
            ':password_hash' => $user_data['password'],
            ':role' => $user_data['role'] ?? 'client',
            ':full_name' => $user_data['full_name'] ?? $user_data['username'],
            ':organization' => $user_data['organization'] ?? null,
            ':phone' => $user_data['phone'] ?? null,
            ':status' => $status,
        ]);
        
        return $result;
    } catch (Throwable $e) {
        error_log("createUser error: " . $e->getMessage());
        return false;
    }
}

function authenticateUser($username, $password) {
    startSession();
    
    // Try database first
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, full_name, organization, status FROM users WHERE username = :username OR email = :email LIMIT 1');
        if (!$stmt) {
            return false;
        }
        
        $executeResult = $stmt->execute([
            ':username' => $username,
            ':email' => $username  // Same value for both username and email search
        ]);
        if (!$executeResult) {
            return false;
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if password_hash is valid
            if (empty($user['password_hash'])) {
                error_log("Login failed: Empty password hash for user: {$user['username']}");
                return false;
            }
            
            // Verify password first
            $verifyResult = password_verify($password, $user['password_hash']);
            
            if (!$verifyResult) {
                error_log("Login failed: Password verification failed for user: {$user['username']}");
                return false;
            }
            
            if ($verifyResult) {
                // Check if user is approved/active (not pending or inactive)
                $userStatus = strtolower(trim($user['status'] ?? 'pending'));
                error_log("Login attempt for user: {$user['username']}, status: '$userStatus'");
                if ($userStatus === 'pending' || $userStatus === 'inactive') {
                    error_log("Login blocked: User status is '$userStatus' (username: {$user['username']})");
                    return false; // User not approved or inactive
                }
                try {
                    // Ensure ID is an integer to avoid type issues
                    $userId = (int)$user['id'];
                    $userRole = !empty($user['role']) ? $user['role'] : 'client';
                    
                    $_SESSION['user_role'] = $userRole;
                    $_SESSION['user_data'] = [
                        'id' => $userId,
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'] ?? $user['username'],
                        'role' => $userRole,
                        'organization' => $user['organization'] ?? 'MDRRMO'
                    ];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $user['username'];
                    return true;
                } catch (Throwable $sessionError) {
                    error_log("Login error: " . $sessionError->getMessage());
                    return false;
                }
            }
        }
    } catch (Throwable $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
    
    return false;
}

function getAllUsers() {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return [];
        }
        
        $stmt = $pdo->query('SELECT id, username, email, role, full_name, organization, phone, status, created_at FROM users ORDER BY created_at DESC');
        if (!$stmt) {
            return [];
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("getAllUsers error: " . $e->getMessage());
        return [];
    }
}

function updateUser($user_id, $data) {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        
        // Build update query dynamically based on provided data
        $fields = [];
        $params = [':id' => $user_id];
        
        $allowedFields = ['full_name', 'email', 'organization', 'phone', 'status', 'role'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false; // Nothing to update
        }
        
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        return $stmt->execute($params);
    } catch (Throwable $e) {
        error_log("updateUser error: " . $e->getMessage());
        return false;
    }
}

function deleteUser($user_id) {
    try {
        $pdo = getPdoConnection();
        if (!$pdo) {
            return false;
        }
        
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        if (!$stmt) {
            return false;
        }
        
        return $stmt->execute([':id' => $user_id]);
    } catch (Throwable $e) {
        error_log("deleteUser error: " . $e->getMessage());
        return false;
    }
}

function approveUser($user_id) {
    return updateUser($user_id, ['status' => 'approved']);
}

function rejectUser($user_id) {
    return updateUser($user_id, ['status' => 'inactive']);
}
// Intentionally no closing PHP tag to prevent accidental output
