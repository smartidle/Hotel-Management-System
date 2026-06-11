<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('add_room');
$active_page = 'rooms';
$baseUrl = getBaseUrl();

$types = $pdo->query("SELECT * FROM room_types ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitize($_POST['room_number'] ?? '');
    $room_type_id = (int)($_POST['room_type_id'] ?? 0);
    $floor = (int)($_POST['floor'] ?? 1);
    $status = sanitize($_POST['status'] ?? 'available');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($room_number) || empty($room_type_id)) {
        setFlash('error', t('required_field'));
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$room_number, $room_type_id, $floor, $status, $notes]);
            logActivity($pdo, $_SESSION['user_id'], 'create', 'rooms', "Created room $room_number");
            setFlash('success', t('room_created'));
            redirect($baseUrl . '/modules/rooms/index.php');
        } catch (Exception $e) {
            setFlash('error', t('operation_failed'));
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-door-open"></i> <?php echo t('add_room'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/rooms/index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> <?php echo t('back'); ?>
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('room_number'); ?> *</label>
                    <input type="text" name="room_number" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('room_type'); ?> *</label>
                    <select name="room_type_id" class="form-select" required>
                        <option value="">-- <?php echo t('select_room'); ?> --</option>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?> - <?php echo formatCurrency($type['base_price']); ?>/night</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('floor'); ?></label>
                    <input type="number" name="floor" class="form-control" value="1" min="1" max="20">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('status'); ?></label>
                    <select name="status" class="form-select">
                        <option value="available"><?php echo t('status_available'); ?></option>
                        <option value="maintenance"><?php echo t('status_maintenance'); ?></option>
                        <option value="reserved"><?php echo t('status_reserved'); ?></option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label"><?php echo t('notes'); ?></label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('save'); ?></button>
                    <a href="<?php echo $baseUrl; ?>/modules/rooms/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
