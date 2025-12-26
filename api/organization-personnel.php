<?php
/**
 * API endpoint for organization personnel management
 * Supports GET (list all), POST (create), PUT (update), DELETE operations
 */

define('SECURE_ACCESS', true);
require_once '../auth.php';

header('Content-Type: application/json');

// Check if user is logged in
checkLogin();

$method = $_SERVER['REQUEST_METHOD'];
$isAdmin = getUserRole() === 'admin';

try {
    $pdo = getPdoConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS organization_personnel (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        role VARCHAR(255) NOT NULL,
        is_ceo TINYINT(1) NOT NULL DEFAULT 0,
        reports_to INT UNSIGNED DEFAULT NULL,
        photo_data_url LONGTEXT DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (reports_to) REFERENCES organization_personnel(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    switch ($method) {
        case 'GET':
            // Get all personnel (both admin and client can view)
            $stmt = $pdo->query('SELECT id, name, role, is_ceo, reports_to, photo_data_url, created_at, updated_at FROM organization_personnel ORDER BY created_at ASC');
            $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to format expected by frontend
            $result = array_map(function($person) {
                return [
                    'id' => (string)$person['id'],
                    'name' => $person['name'],
                    'role' => $person['role'],
                    'isCEO' => (bool)$person['is_ceo'],
                    'reportsTo' => $person['reports_to'] ? (string)$person['reports_to'] : null,
                    'photoDataUrl' => $person['photo_data_url'],
                    'createdAt' => strtotime($person['created_at']) * 1000 // Convert to milliseconds
                ];
            }, $personnel);
            
            echo json_encode($result);
            break;

        case 'POST':
            // Create new personnel (admin only)
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized - Admin access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to POST data
            }

            $name = $input['name'] ?? null;
            $role = $input['role'] ?? null;
            $isCEO = isset($input['isCEO']) ? (int)(bool)$input['isCEO'] : 0;
            $reportsTo = !empty($input['reportsTo']) ? (int)$input['reportsTo'] : null;
            $photoDataUrl = $input['photoDataUrl'] ?? null;

            if (!$name || !$role) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and role are required']);
                exit();
            }

            // If setting as CEO, check if another CEO exists
            if ($isCEO) {
                $stmt = $pdo->prepare('SELECT id FROM organization_personnel WHERE is_ceo = 1 LIMIT 1');
                $stmt->execute();
                $existingCEO = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existingCEO) {
                    // Remove CEO status from existing CEO
                    $stmt = $pdo->prepare('UPDATE organization_personnel SET is_ceo = 0 WHERE id = ?');
                    $stmt->execute([$existingCEO['id']]);
                }
            }

            $stmt = $pdo->prepare('INSERT INTO organization_personnel (name, role, is_ceo, reports_to, photo_data_url) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $role, $isCEO, $reportsTo, $photoDataUrl]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT id, name, role, is_ceo, reports_to, photo_data_url, created_at FROM organization_personnel WHERE id = ?');
            $stmt->execute([$newId]);
            $newPerson = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'id' => (string)$newPerson['id'],
                'name' => $newPerson['name'],
                'role' => $newPerson['role'],
                'isCEO' => (bool)$newPerson['is_ceo'],
                'reportsTo' => $newPerson['reports_to'] ? (string)$newPerson['reports_to'] : null,
                'photoDataUrl' => $newPerson['photo_data_url'],
                'createdAt' => strtotime($newPerson['created_at']) * 1000
            ]);
            break;

        case 'PUT':
            // Update personnel (admin only)
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized - Admin access required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID is required']);
                exit();
            }

            $name = $input['name'] ?? null;
            $role = $input['role'] ?? null;
            $isCEO = isset($input['isCEO']) ? (int)(bool)$input['isCEO'] : 0;
            $reportsTo = !empty($input['reportsTo']) ? (int)$input['reportsTo'] : null;
            $photoDataUrl = isset($input['photoDataUrl']) ? $input['photoDataUrl'] : null;

            if (!$name || !$role) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and role are required']);
                exit();
            }

            // If setting as CEO, check if another CEO exists
            if ($isCEO) {
                $stmt = $pdo->prepare('SELECT id FROM organization_personnel WHERE is_ceo = 1 AND id != ? LIMIT 1');
                $stmt->execute([$id]);
                $existingCEO = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existingCEO) {
                    // Remove CEO status from existing CEO
                    $stmt = $pdo->prepare('UPDATE organization_personnel SET is_ceo = 0 WHERE id = ?');
                    $stmt->execute([$existingCEO['id']]);
                }
            }

            $stmt = $pdo->prepare('UPDATE organization_personnel SET name = ?, role = ?, is_ceo = ?, reports_to = ?, photo_data_url = ? WHERE id = ?');
            $stmt->execute([$name, $role, $isCEO, $reportsTo, $photoDataUrl, $id]);

            $stmt = $pdo->prepare('SELECT id, name, role, is_ceo, reports_to, photo_data_url, updated_at FROM organization_personnel WHERE id = ?');
            $stmt->execute([$id]);
            $updatedPerson = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'id' => (string)$updatedPerson['id'],
                'name' => $updatedPerson['name'],
                'role' => $updatedPerson['role'],
                'isCEO' => (bool)$updatedPerson['is_ceo'],
                'reportsTo' => $updatedPerson['reports_to'] ? (string)$updatedPerson['reports_to'] : null,
                'photoDataUrl' => $updatedPerson['photo_data_url'],
                'createdAt' => strtotime($updatedPerson['updated_at']) * 1000
            ]);
            break;

        case 'DELETE':
            // Delete personnel (admin only)
            if (!$isAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized - Admin access required']);
                exit();
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID is required']);
                exit();
            }

            $stmt = $pdo->prepare('DELETE FROM organization_personnel WHERE id = ?');
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

