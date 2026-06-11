<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // 确保 housekeeping_tasks 表存在
    $pdo->exec("CREATE TABLE IF NOT EXISTS housekeeping_tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id INTEGER NOT NULL,
        task_type TEXT NOT NULL DEFAULT 'cleaning' CHECK(task_type IN ('cleaning','bed_change','maintenance')),
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending','in_progress','completed')),
        assigned_to TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id)
    )");

    // 确保 housekeeping_status 字段存在
    try {
        $pdo->exec("ALTER TABLE rooms ADD COLUMN housekeeping_status TEXT DEFAULT 'clean'");
    } catch (Exception $e) { /* column already exists */ }

    switch ($action) {
        case 'update_status':
            $roomId = intval($_POST['room_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            $validStatuses = ['clean', 'dirty', 'inspecting', 'maintenance'];
            if ($roomId <= 0 || !in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid room ID or status.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
            $stmt->execute([$status, $roomId]);

            // Also update room notes if provided
            if (!empty($notes)) {
                $stmt = $pdo->prepare("UPDATE rooms SET notes = ? WHERE id = ? AND (notes IS NULL OR notes = '')");
                $stmt->execute([$notes, $roomId]);
            }

            logActivity($pdo, $_SESSION['user_id'], 'update_status', 'housekeeping', "Updated room ID $roomId housekeeping status to $status");
            echo json_encode(['success' => true, 'message' => 'Room status updated successfully.']);
            break;

        case 'add_task':
            $roomId = intval($_POST['room_id'] ?? 0);
            $taskType = sanitize($_POST['task_type'] ?? 'cleaning');
            $assignedTo = sanitize($_POST['assigned_to'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');

            $validTaskTypes = ['cleaning', 'bed_change', 'maintenance'];
            if ($roomId <= 0 || !in_array($taskType, $validTaskTypes)) {
                echo json_encode(['success' => false, 'error' => 'Invalid room ID or task type.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO housekeeping_tasks (room_id, task_type, assigned_to, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$roomId, $taskType, $assignedTo, $notes]);

            logActivity($pdo, $_SESSION['user_id'], 'add_task', 'housekeeping', "Added $taskType task for room ID $roomId");
            echo json_encode(['success' => true, 'message' => 'Task added successfully.', 'id' => $pdo->lastInsertId()]);
            break;

        case 'update_task':
            $taskId = intval($_POST['task_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');

            $validStatuses = ['pending', 'in_progress', 'completed'];
            if ($taskId <= 0 || !in_array($status, $validStatuses)) {
                echo json_encode(['success' => false, 'error' => 'Invalid task ID or status.']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE housekeeping_tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $taskId]);

            // If task completed, update room housekeeping status to clean
            if ($status === 'completed') {
                $stmt = $pdo->prepare("SELECT room_id FROM housekeeping_tasks WHERE id = ?");
                $stmt->execute([$taskId]);
                $task = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($task) {
                    $stmt = $pdo->prepare("UPDATE rooms SET housekeeping_status = 'clean' WHERE id = ?");
                    $stmt->execute([$task['room_id']]);
                }
            }

            logActivity($pdo, $_SESSION['user_id'], 'update_task', 'housekeeping', "Updated task ID $taskId status to $status");
            echo json_encode(['success' => true, 'message' => 'Task status updated successfully.']);
            break;

        case 'tasks':
            $date = sanitize($_GET['date'] ?? date('Y-m-d'));
            $stmt = $pdo->prepare("
                SELECT ht.*, r.room_number
                FROM housekeeping_tasks ht
                JOIN rooms r ON ht.room_id = r.id
                WHERE DATE(ht.created_at) = ?
                ORDER BY ht.status ASC, ht.created_at DESC
            ");
            $stmt->execute([$date]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $tasks]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
