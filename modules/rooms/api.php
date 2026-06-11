<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'delete':
            if ($_SESSION['role_id'] != ROLE_ADMIN) {
                echo json_encode(['success' => false, 'error' => t('access_denied')]);
                exit();
            }
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            logActivity($pdo, $_SESSION['user_id'], 'delete', 'rooms', "Deleted room ID $id");
            echo json_encode(['success' => true, 'message' => t('room_deleted')]);
            break;

        case 'update_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');
            $valid = ['available', 'occupied', 'maintenance', 'reserved'];
            if (!in_array($status, $valid)) {
                echo json_encode(['success' => false, 'error' => 'Invalid status']);
                exit();
            }
            $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_available':
            $checkIn = $_GET['check_in'] ?? '';
            $checkOut = $_GET['check_out'] ?? '';
            $stmt = $pdo->prepare("
                SELECT r.*, rt.name as type_name, rt.base_price 
                FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.status = 'available' 
                AND r.id NOT IN (
                    SELECT room_id FROM reservations 
                    WHERE status NOT IN ('cancelled', 'no_show') 
                    AND (check_in_date < ? AND check_out_date > ?)
                )
                ORDER BY r.floor, r.room_number
            ");
            $stmt->execute([$checkOut, $checkIn]);
            $rooms = $stmt->fetchAll();
            echo json_encode(['success' => true, 'rooms' => $rooms]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
