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

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    session_destroy();
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
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return true;
        }
    }
    return false;
}

function emailExists($email) {
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return true;
        }
    }
    return false;
}

function createUser($user_data) {
    $users = loadUsers();
    
    // Add user ID
    $user_data['id'] = count($users) + 1;
    
    $users[] = $user_data;
    
    return saveUsers($users);
}

function authenticateUser($username, $password) {
    startSession();
    
    // Try database first
    try {
        $pdo = getPdoConnection();
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, full_name, organization FROM users WHERE username = :u OR email = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_role'] = $user['role'] ?: 'client';
            $_SESSION['user_data'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'] ?? $user['username'],
                'role' => $_SESSION['user_role'],
                'organization' => $user['organization'] ?? 'MDRRMO'
            ];
            return true;
        }
    } catch (Throwable $e) {
        // Fall through to demo/file-based auth
    }
    
    // First check demo credentials
    $demo_credentials = [
        'admin' => 'mdrrmo2024',
        'client' => 'client2024'
    ];
    
    if (isset($demo_credentials[$username]) && $demo_credentials[$username] === $password) {
        // Set demo user data
        $_SESSION['user_role'] = $username;
        $_SESSION['user_data'] = [
            'username' => $username,
            'full_name' => ucfirst($username),
            'role' => $_SESSION['user_role'],
            'organization' => 'MDRRMO Demo'
        ];
        return true;
    }
    
    // Check registered users (file-based fallback)
    $users = loadUsers();
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_data'] = $user;
            return true;
        }
    }
    
    return false;
}

function getAllUsers() {
    return loadUsers();
}

function updateUser($user_id, $data) {
    $users = loadUsers();
    foreach ($users as &$user) {
        if ($user['id'] == $user_id) {
            $user = array_merge($user, $data);
            return saveUsers($users);
        }
    }
    return false;
}

function deleteUser($user_id) {
    $users = loadUsers();
    foreach ($users as $key => $user) {
        if ($user['id'] == $user_id) {
            unset($users[$key]);
            return saveUsers(array_values($users));
        }
    }
    return false;
}
// Intentionally no closing PHP tag to prevent accidental output
