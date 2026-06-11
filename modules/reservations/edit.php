<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('edit_reservation');
$active_page = 'reservations';
$module_js = 'reservations';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect($baseUrl . '/modules/reservations/index.php');

$stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
$stmt->execute([$id]);
$reservation = $stmt->fetch();
if (!$reservation) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/reservations/index.php'); }

// Only allow editing pending and confirmed
if (!in_array($reservation['status'], ['pending', 'confirmed'])) {
    setFlash('error', t('access_denied'));
    redirect($baseUrl . '/modules/reservations/view.php?id=' . $id);
}

$guests = $pdo->query("SELECT id, first_name, last_name, phone FROM guests ORDER BY last_name")->fetchAll();
$types = $pdo->query("SELECT * FROM room_types ORDER BY id")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)($_POST['guest_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $check_in = sanitize($_POST['check_in_date'] ?? '');
    $check_out = sanitize($_POST['check_out_date'] ?? '');
    $num_guests = (int)($_POST['num_guests'] ?? 1);
    $special = sanitize($_POST['special_requests'] ?? '');
    $status = sanitize($_POST['status'] ?? $reservation['status']);

    // Calculate total
    $nights = daysBetween($check_in, $check_out);
    $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
    $stmt->execute([$room_id]);
    $roomInfo = $stmt->fetch();
    $total = $nights * ($roomInfo['base_price'] ?? 0);

    try {
        $stmt = $pdo->prepare("UPDATE reservations SET guest_id=?, room_id=?, check_in_date=?, check_out_date=?, num_guests=?, special_requests=?, total_amount=?, status=? WHERE id=?");
        $stmt->execute([$guest_id, $room_id, $check_in, $check_out, $num_guests, $special, $total, $status, $id]);
        logActivity($pdo, $_SESSION['user_id'], 'update', 'reservations', "Updated reservation ID $id");
        setFlash('success', t('reservation_updated'));
        redirect($baseUrl . '/modules/reservations/view.php?id=' . $id);
    } catch (Exception $e) {
        setFlash('error', t('operation_failed'));
    }
}

// Get available rooms + current room
$rooms = $pdo->query("
    SELECT r.*, rt.name as type_name, rt.base_price FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE r.status IN ('available', 'reserved') OR r.id = {$reservation['room_id']}
    ORDER BY r.room_number
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-calendar-check"></i> <?php echo t('edit_reservation'); ?> - <?php echo htmlspecialchars($reservation['reservation_code']); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/reservations/view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card"><div class="card-body">
<form method="POST" action="">
    <input type="hidden" name="status" value="<?php echo $reservation['status']; ?>">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label"><?php echo t('select_guest'); ?> *</label>
            <select name="guest_id" class="form-select" required>
                <?php foreach ($guests as $g): ?>
                <option value="<?php echo $g['id']; ?>" <?php echo $reservation['guest_id'] == $g['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['first_name'] . ' ' . $g['last_name'] . ' (' . $g['phone'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label"><?php echo t('select_room'); ?> *</label>
            <select name="room_id" class="form-select" required>
                <?php foreach ($rooms as $rm): ?>
                <option value="<?php echo $rm['id']; ?>" data-price="<?php echo $rm['base_price']; ?>" <?php echo $reservation['room_id'] == $rm['id'] ? 'selected' : ''; ?>>
                    Room <?php echo $rm['room_number']; ?> - <?php echo $rm['type_name']; ?> (<?php echo formatCurrency($rm['base_price']); ?>/night)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('check_in_date'); ?> *</label>
            <input type="date" name="check_in_date" id="check_in_date" class="form-control" value="<?php echo $reservation['check_in_date']; ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('check_out_date'); ?> *</label>
            <input type="date" name="check_out_date" id="check_out_date" class="form-control" value="<?php echo $reservation['check_out_date']; ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('num_guests'); ?></label>
            <input type="number" name="num_guests" class="form-control" value="<?php echo $reservation['num_guests']; ?>" min="1" max="10">
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('total_amount'); ?></label>
            <input type="text" class="form-control" value="<?php echo formatCurrency($reservation['total_amount']); ?>" disabled>
        </div>
        <div class="col-12">
            <label class="form-label"><?php echo t('special_requests'); ?></label>
            <textarea name="special_requests" class="form-control" rows="2"><?php echo htmlspecialchars($reservation['special_requests'] ?? ''); ?></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('update'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/reservations/view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
        </div>
    </div>
</form>
</div></div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
