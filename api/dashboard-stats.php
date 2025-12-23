<?php
/**
 * API endpoint for admin dashboard statistics
 * Returns JSON data for dashboard metrics
 */

define('SECURE_ACCESS', true);
require_once '../auth.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
checkLogin();

if (getUserRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = getPdoConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get total users count (regardless of approval status)
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM users');
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get pending users count
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users WHERE status = "pending"');
    $stmt->execute();
    $pendingUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get approved/active users count
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users WHERE status IN ("approved", "active")');
    $stmt->execute();
    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // For incidents/reports, we'll use localStorage data on client-side
    // But we can prepare the structure here for future database integration
    
    $stats = [
        'users' => [
            'total' => (int)$totalUsers,
            'pending' => (int)$pendingUsers,
            'active' => (int)$activeUsers
        ],
        'incidents' => [
            // These will be populated by JavaScript from localStorage
            'total' => 0,
            'pending' => 0
        ],
        'timestamp' => time()
    ];
    
    echo json_encode($stats, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

