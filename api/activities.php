<?php
/**
 * API endpoint for activities
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

// Ensure activities table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activities (
            id VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            images LONGTEXT DEFAULT NULL,
            created_by VARCHAR(255) NOT NULL,
            created_at BIGINT NOT NULL,
            updated_at BIGINT DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_created_by (created_by),
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
        // Fetch activities
        try {
            $query = 'SELECT id, title, description, images, created_by, created_at, updated_at FROM activities ORDER BY created_at DESC';
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert database format to frontend format
            $formattedActivities = array_map(function($activity) {
                $images = $activity['images'] ? json_decode($activity['images'], true) : [];
                return [
                    'id' => $activity['id'],
                    'title' => $activity['title'],
                    'description' => $activity['description'],
                    'images' => is_array($images) ? $images : [],
                    'createdBy' => $activity['created_by'],
                    'createdAt' => (int)$activity['created_at'],
                    'updatedAt' => $activity['updated_at'] ? (int)$activity['updated_at'] : null
                ];
            }, $activities);
            
            echo json_encode($formattedActivities);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new activity
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required field: title']);
                exit();
            }
            
            $id = $data['id'] ?? uniqid('activity_', true);
            $title = $data['title'];
            $description = $data['description'] ?? null;
            $images = isset($data['images']) && is_array($data['images']) ? json_encode($data['images']) : '[]';
            $createdBy = $currentUser;
            $createdAt = $data['createdAt'] ?? (time() * 1000);
            
            $stmt = $pdo->prepare('
                INSERT INTO activities (id, title, description, images, created_by, created_at)
                VALUES (:id, :title, :description, :images, :created_by, :created_at)
            ');
            
            $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':description' => $description,
                ':images' => $images,
                ':created_by' => $createdBy,
                ':created_at' => $createdAt
            ]);
            
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        // Update activity
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
            
            if (isset($data['title'])) {
                $updates[] = 'title = :title';
                $params[':title'] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updates[] = 'description = :description';
                $params[':description'] = $data['description'];
            }
            
            if (isset($data['images']) && is_array($data['images'])) {
                $updates[] = 'images = :images';
                $params[':images'] = json_encode($data['images']);
            }
            
            if (!empty($updates)) {
                $updates[] = 'updated_at = :updated_at';
                $params[':updated_at'] = time() * 1000;
                
                $query = 'UPDATE activities SET ' . implode(', ', $updates) . ' WHERE id = :id';
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
        // Delete activity
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameter: id']);
                exit();
            }
            
            $stmt = $pdo->prepare('DELETE FROM activities WHERE id = :id');
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

