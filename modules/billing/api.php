<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_payment':
            $bill_id = (int)($_POST['bill_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $method = sanitize($_POST['payment_method'] ?? 'cash');
            $ref = sanitize($_POST['reference_number'] ?? '');

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, processed_by) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$bill_id, $amount, $method, $now, $ref, $_SESSION['user_id']]);

            // Update bill status
            $paid = $pdo->prepare("SELECT COALESCE(SUM(amount),0) as total FROM payments WHERE bill_id = ?");
            $paid->execute([$bill_id]);
            $totalPaid = (float)$paid->fetch()['total'];

            $billStmt = $pdo->prepare("SELECT total_amount FROM bills WHERE id = ?");
            $billStmt->execute([$bill_id]);
            $billTotal = (float)$billStmt->fetch()['total_amount'];

            $newStatus = 'unpaid';
            if ($totalPaid >= $billTotal) $newStatus = 'paid';
            elseif ($totalPaid > 0) $newStatus = 'partial';

            $pdo->prepare("UPDATE bills SET status = ? WHERE id = ?")->execute([$newStatus, $bill_id]);
            logActivity($pdo, $_SESSION['user_id'], 'payment', 'billing', "Added payment for bill ID $bill_id");
            echo json_encode(['success' => true]);
            break;

        case 'get_bill':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT b.*, g.first_name, g.last_name FROM bills b JOIN guests g ON b.guest_id = g.id WHERE b.id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'bill' => $stmt->fetch()]);
            break;

        case 'get_reservations':
            $stmt = $pdo->query("SELECT r.*, g.first_name, g.last_name FROM reservations r JOIN guests g ON r.guest_id = g.id WHERE r.status = 'checked_out' ORDER BY r.check_out_date DESC");
            echo json_encode(['success' => true, 'reservations' => $stmt->fetchAll()]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
