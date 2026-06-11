<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('edit_room');
$active_page = 'rooms';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect($baseUrl . '/modules/rooms/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/rooms/index.php'); }

$types = $pdo->query("SELECT * FROM room_types ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitize($_POST['room_number'] ?? '');
    $room_type_id = (int)($_POST['room_type_id'] ?? 0);
    $floor = (int)($_POST['floor'] ?? 1);
    $status = sanitize($_POST['status'] ?? 'available');
    $notes = sanitize($_POST['notes'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type_id=?, floor=?, status=?, notes=? WHERE id=?");
        $stmt->execute([$room_number, $room_type_id, $floor, $status, $notes, $id]);
        logActivity($pdo, $_SESSION['user_id'], 'update', 'rooms', "Updated room $room_number");
        setFlash('success', t('room_updated'));
        redirect($baseUrl . '/modules/rooms/index.php');
    } catch (Exception $e) {
        setFlash('error', t('operation_failed'));
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-door-open"></i> <?php echo t('edit_room'); ?> - <?php echo htmlspecialchars($room['room_number']); ?></h4>
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
                    <input type="text" name="room_number" class="form-control" value="<?php echo htmlspecialchars($room['room_number']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('room_type'); ?> *</label>
                    <select name="room_type_id" class="form-select" required>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo $room['room_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?> - <?php echo formatCurrency($type['base_price']); ?>/night
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('floor'); ?></label>
                    <input type="number" name="floor" class="form-control" value="<?php echo $room['floor']; ?>" min="1" max="20">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo t('status'); ?></label>
                    <select name="status" class="form-select">
                        <option value="available" <?php echo $room['status'] === 'available' ? 'selected' : ''; ?>><?php echo t('status_available'); ?></option>
                        <option value="occupied" <?php echo $room['status'] === 'occupied' ? 'selected' : ''; ?>><?php echo t('status_occupied'); ?></option>
                        <option value="maintenance" <?php echo $room['status'] === 'maintenance' ? 'selected' : ''; ?>><?php echo t('status_maintenance'); ?></option>
                        <option value="reserved" <?php echo $room['status'] === 'reserved' ? 'selected' : ''; ?>><?php echo t('status_reserved'); ?></option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label"><?php echo t('notes'); ?></label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($room['notes'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('update'); ?></button>
                    <a href="<?php echo $baseUrl; ?>/modules/rooms/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
