<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

// Admin only
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    echo json_encode(['success' => false, 'error' => t('access_denied')]);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit(); }
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Cannot toggle own status']);
                exit();
            }
            $stmt = $pdo->prepare("SELECT status FROM staff WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            if ($current === false) { echo json_encode(['success' => false, 'message' => 'Staff not found']); exit(); }
            $newStatus = $current === 'active' ? 'inactive' : 'active';
            $pdo->prepare("UPDATE staff SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            logActivity($pdo, $_SESSION['user_id'], 'toggle_status', 'staff', "Toggled status for staff ID $id to $newStatus");
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit(); }
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete yourself']);
                exit();
            }
            $pdo->prepare("DELETE FROM staff WHERE id = ?")->execute([$id]);
            logActivity($pdo, $_SESSION['user_id'], 'delete', 'staff', "Deleted staff ID $id");
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
