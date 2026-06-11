<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search':
        $q = $_GET['query'] ?? '';
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, phone, email, nationality FROM guests WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ? LIMIT 10");
        $like = "%$q%";
        $stmt->execute([$like, $like, $like, $like]);
        echo json_encode(['success' => true, 'guests' => $stmt->fetchAll()]);
        break;
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'guest' => $stmt->fetch()]);
        break;
    case 'delete':
        if ($_SESSION['role_id'] != ROLE_ADMIN) { echo json_encode(['success' => false]); exit(); }
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM guests WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
