<?php
/**
 * API endpoint for incidents
 * Handles GET (fetch), POST (create), PUT (update), DELETE (delete) operations
 */

define('SECURE_ACCESS', true);
require_once '../auth.php';
require_once '../config.php';

header('Content-Type: application/json');

checkLogin();

$pdo = getPdoConnection();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Ensure incidents table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS incidents (
            id VARCHAR(255) NOT NULL,
            type VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'New',
            reported_by VARCHAR(255) NOT NULL,
            severity VARCHAR(50) DEFAULT NULL,
            lat DECIMAL(10, 8) DEFAULT NULL,
            lng DECIMAL(11, 8) DEFAULT NULL,
            photo_data_url LONGTEXT DEFAULT NULL,
            created_at BIGINT NOT NULL,
            updated_at BIGINT DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_reported_by (reported_by),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    // Table might already exist, continue
}

$method = $_SERVER['REQUEST_METHOD'];
$userRole = getUserRole();
$currentUser = getCurrentUser();

switch ($method) {
    case 'GET':
        // Fetch incidents
        try {
            $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
            $status = isset($_GET['status']) ? $_GET['status'] : null;
            
            $query = 'SELECT id, type, description, status, reported_by, severity, lat, lng, photo_data_url, created_at, updated_at FROM incidents WHERE 1=1';
            $params = [];
            
            // Allow filtering by user if provided (for admin filtering by specific user)
            // Clients now see ALL incidents (as per requirements)
            if ($userId) {
                $query .= ' AND reported_by = :reported_by';
                $params[':reported_by'] = $userId;
            }
            
            // Filter by status if provided
            if ($status && $status !== 'All') {
                $query .= ' AND status = :status';
                $params[':status'] = $status;
            }
            
            $query .= ' ORDER BY created_at DESC';
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert database format to frontend format
            $formattedIncidents = array_map(function($incident) {
                return [
                    'id' => $incident['id'],
                    'type' => $incident['type'],
                    'description' => $incident['description'],
                    'status' => $incident['status'],
                    'reportedBy' => $incident['reported_by'],
                    'severity' => $incident['severity'],
                    'lat' => $incident['lat'] ? (float)$incident['lat'] : null,
                    'lng' => $incident['lng'] ? (float)$incident['lng'] : null,
                    'photoDataUrl' => $incident['photo_data_url'],
                    'createdAt' => (int)$incident['created_at'],
                    'updatedAt' => $incident['updated_at'] ? (int)$incident['updated_at'] : null
                ];
            }, $incidents);
            
            echo json_encode($formattedIncidents);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new incident (both admin and client can create)
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['type']) || !isset($data['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: type, description']);
                exit();
            }
            
            $id = $data['id'] ?? uniqid('inc_', true);
            $type = $data['type'];
            $description = $data['description'];
            $status = $data['status'] ?? 'New';
            $reportedBy = $data['reportedBy'] ?? $currentUser;
            $severity = $data['severity'] ?? null;
            $lat = isset($data['lat']) ? $data['lat'] : null;
            $lng = isset($data['lng']) ? $data['lng'] : null;
            $photoDataUrl = $data['photoDataUrl'] ?? null;
            $createdAt = $data['createdAt'] ?? (time() * 1000); // Convert to milliseconds
            
            $stmt = $pdo->prepare('
                INSERT INTO incidents (id, type, description, status, reported_by, severity, lat, lng, photo_data_url, created_at)
                VALUES (:id, :type, :description, :status, :reported_by, :severity, :lat, :lng, :photo_data_url, :created_at)
            ');
            
            $stmt->execute([
                ':id' => $id,
                ':type' => $type,
                ':description' => $description,
                ':status' => $status,
                ':reported_by' => $reportedBy,
                ':severity' => $severity,
                ':lat' => $lat,
                ':lng' => $lng,
                ':photo_data_url' => $photoDataUrl,
                ':created_at' => $createdAt
            ]);
            
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update incident (admin only for status updates)
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: Only admins can update incidents']);
            exit();
        }
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required field: id']);
                exit();
            }
            
            $id = $data['id'];
            $updates = [];
            $params = [':id' => $id];
            
            if (isset($data['status'])) {
                $updates[] = 'status = :status';
                $params[':status'] = $data['status'];
            }
            
            if (isset($data['description'])) {
                $updates[] = 'description = :description';
                $params[':description'] = $data['description'];
            }
            
            if (!empty($updates)) {
                $updates[] = 'updated_at = :updated_at';
                $params[':updated_at'] = time() * 1000;
                
                $query = 'UPDATE incidents SET ' . implode(', ', $updates) . ' WHERE id = :id';
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'No fields to update']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Delete incident (admin only)
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: Only admins can delete incidents']);
            exit();
        }
        
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameter: id']);
                exit();
            }
            
            $stmt = $pdo->prepare('DELETE FROM incidents WHERE id = :id');
            $stmt->execute([':id' => $id]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

