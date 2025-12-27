<?php
/**
 * API endpoint for equipment
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

// Ensure equipment table exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS equipment (
            id VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            count INT NOT NULL DEFAULT 1,
            image_data_url LONGTEXT DEFAULT NULL,
            created_by VARCHAR(255) NOT NULL,
            created_at BIGINT NOT NULL,
            updated_at BIGINT DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_created_by (created_by),
            INDEX idx_name (name)
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
        // Fetch equipment
        try {
            $query = 'SELECT id, name, count, image_data_url, created_by, created_at, updated_at FROM equipment ORDER BY name ASC';
            
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert database format to frontend format
            $formattedEquipment = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'count' => (int)$item['count'],
                    'imageDataUrl' => $item['image_data_url'],
                    'createdBy' => $item['created_by'],
                    'createdAt' => (int)$item['created_at'],
                    'updatedAt' => $item['updated_at'] ? (int)$item['updated_at'] : null
                ];
            }, $equipment);
            
            echo json_encode($formattedEquipment);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'POST':
        // Create new equipment
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['name']) || !isset($data['count'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: name, count']);
                exit();
            }
            
            $id = $data['id'] ?? uniqid('equipment_', true);
            $name = $data['name'];
            $count = (int)$data['count'];
            $imageDataUrl = $data['imageDataUrl'] ?? null;
            $createdBy = $currentUser;
            $createdAt = $data['createdAt'] ?? (time() * 1000);
            
            $stmt = $pdo->prepare('
                INSERT INTO equipment (id, name, count, image_data_url, created_by, created_at)
                VALUES (:id, :name, :count, :image_data_url, :created_by, :created_at)
            ');
            
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':count' => $count,
                ':image_data_url' => $imageDataUrl,
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
        // Update equipment
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
            
            if (isset($data['name'])) {
                $updates[] = 'name = :name';
                $params[':name'] = $data['name'];
            }
            
            if (isset($data['count'])) {
                $updates[] = 'count = :count';
                $params[':count'] = (int)$data['count'];
            }
            
            if (isset($data['imageDataUrl'])) {
                $updates[] = 'image_data_url = :image_data_url';
                $params[':image_data_url'] = $data['imageDataUrl'];
            }
            
            if (!empty($updates)) {
                $updates[] = 'updated_at = :updated_at';
                $params[':updated_at'] = time() * 1000;
                
                $query = 'UPDATE equipment SET ' . implode(', ', $updates) . ' WHERE id = :id';
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
        // Delete equipment
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameter: id']);
                exit();
            }
            
            $stmt = $pdo->prepare('DELETE FROM equipment WHERE id = :id');
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

