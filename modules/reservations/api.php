<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'confirm':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ? AND status = 'pending'");
            $stmt->execute([$id]);
            logActivity($pdo, $_SESSION['user_id'], 'confirm', 'reservations', "Confirmed reservation ID $id");
            setFlash('success', t('reservation_confirmed'));
            echo json_encode(['success' => true]);
            break;

        case 'cancel':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND status IN ('pending','confirmed')");
            $stmt->execute([$id]);
            // Also free up the room if it was reserved
            $res = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
            $res->execute([$id]);
            $roomId = $res->fetchColumn();
            if ($roomId) {
                $pdo->prepare("UPDATE rooms SET status = 'available' WHERE id = ? AND status = 'reserved'")->execute([$roomId]);
            }
            logActivity($pdo, $_SESSION['user_id'], 'cancel', 'reservations', "Cancelled reservation ID $id");
            echo json_encode(['success' => true]);
            break;

        case 'search':
            $q = $_GET['query'] ?? '';
            $stmt = $pdo->prepare("
                SELECT r.*, g.first_name, g.last_name, rm.room_number 
                FROM reservations r
                JOIN guests g ON r.guest_id = g.id
                JOIN rooms rm ON r.room_id = rm.id
                WHERE r.reservation_code LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?
                ORDER BY r.created_at DESC LIMIT 20
            ");
            $like = "%$q%";
            $stmt->execute([$like, $like, $like]);
            echo json_encode(['success' => true, 'results' => $stmt->fetchAll()]);
            break;

        case 'get_available_rooms':
            $checkIn = $_GET['check_in'] ?? '';
            $checkOut = $_GET['check_out'] ?? '';
            $stmt = $pdo->prepare("
                SELECT r.*, rt.name as type_name, rt.base_price 
                FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.status = 'available' 
                AND r.id NOT IN (
                    SELECT room_id FROM reservations 
                    WHERE status NOT IN ('cancelled','no_show') 
                    AND (check_in_date < ? AND check_out_date > ?)
                )
                ORDER BY r.floor, r.room_number
            ");
            $stmt->execute([$checkOut, $checkIn]);
            echo json_encode(['success' => true, 'rooms' => $stmt->fetchAll()]);
            break;

        case 'quick_create_guest':
            $first = sanitize($_POST['first_name'] ?? '');
            $last = sanitize($_POST['last_name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, phone) VALUES (?,?,?)");
            $stmt->execute([$first, $last, $phone]);
            echo json_encode(['success' => true, 'guest_id' => $pdo->lastInsertId()]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
