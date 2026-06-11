<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("
                SELECT rt.*,
                    COUNT(r.id) as total_rooms,
                    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available_count
                FROM room_types rt
                LEFT JOIN rooms r ON r.room_type_id = rt.id
                GROUP BY rt.id
                ORDER BY rt.base_price ASC
            ");
            $roomTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $roomTypes]);
            break;

        case 'create':
            $name = sanitize($_POST['name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $basePrice = floatval($_POST['base_price'] ?? 0);
            $maxOccupancy = intval($_POST['max_occupancy'] ?? 2);
            $amenities = $_POST['amenities'] ?? '{}';

            if (empty($name) || $basePrice <= 0) {
                echo json_encode(['success' => false, 'error' => 'Name and valid price are required.']);
                exit();
            }

            // Validate amenities is valid JSON
            json_decode($amenities);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $amenities = '{}';
            }

            $stmt = $pdo->prepare("INSERT INTO room_types (name, description, base_price, max_occupancy, amenities) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $basePrice, $maxOccupancy, $amenities]);

            logActivity($pdo, $_SESSION['user_id'], 'create', 'room_types', "Created room type: $name");
            echo json_encode(['success' => true, 'message' => 'Room type created successfully.', 'id' => $pdo->lastInsertId()]);
            break;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $name = sanitize($_POST['name'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $basePrice = floatval($_POST['base_price'] ?? 0);
            $maxOccupancy = intval($_POST['max_occupancy'] ?? 2);
            $amenities = $_POST['amenities'] ?? '{}';

            if ($id <= 0 || empty($name) || $basePrice <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID, name and valid price are required.']);
                exit();
            }

            json_decode($amenities);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $amenities = '{}';
            }

            $stmt = $pdo->prepare("UPDATE room_types SET name = ?, description = ?, base_price = ?, max_occupancy = ?, amenities = ? WHERE id = ?");
            $stmt->execute([$name, $description, $basePrice, $maxOccupancy, $amenities, $id]);

            logActivity($pdo, $_SESSION['user_id'], 'update', 'room_types', "Updated room type ID $id: $name");
            echo json_encode(['success' => true, 'message' => 'Room type updated successfully.']);
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid ID.']);
                exit();
            }

            // Check if any rooms use this type
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM rooms WHERE room_type_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];

            if ($count > 0) {
                echo json_encode(['success' => false, 'error' => "Cannot delete: $count room(s) are using this type."]);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM room_types WHERE id = ?");
            $stmt->execute([$id]);

            logActivity($pdo, $_SESSION['user_id'], 'delete', 'room_types', "Deleted room type ID $id");
            echo json_encode(['success' => true, 'message' => 'Room type deleted successfully.']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
