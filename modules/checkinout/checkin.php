<?php
/**
 * Check-in Page
 * Search confirmed reservations and process check-in
 */
require_once __DIR__ . '/../../includes/auth_check.php';

$active_page = 'checkin';
$module_js = 'checkinout';

$searchResults = [];
$selectedReservation = null;
$searchTerm = '';

// Handle search
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $like = '%' . $searchTerm . '%';
    $stmt = $pdo->prepare("
        SELECT r.*, g.first_name AS guest_first, g.last_name AS guest_last,
            g.id_type, g.id_number, g.phone AS guest_phone, g.email AS guest_email,
            g.nationality, g.address,
            rm.room_number, rt.name AS room_type_name, rt.base_price,
            rt.max_occupancy, rt.amenities
        FROM reservations r
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.status = ?
            AND (r.reservation_code LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)
        ORDER BY r.check_in_date ASC
    ");
    $stmt->execute([RES_CONFIRMED, $like, $like, $like]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle selecting a reservation
if (isset($_GET['reservation_id']) && intval($_GET['reservation_id']) > 0) {
    $resId = intval($_GET['reservation_id']);
    $stmt = $pdo->prepare("
        SELECT r.*, g.first_name AS guest_first, g.last_name AS guest_last,
            g.id_type, g.id_number, g.phone AS guest_phone, g.email AS guest_email,
            g.nationality, g.address,
            rm.room_number, rt.name AS room_type_name, rt.base_price,
            rt.max_occupancy, rt.amenities
        FROM reservations r
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.id = ? AND r.status = ?
    ");
    $stmt->execute([$resId, RES_CONFIRMED]);
    $selectedReservation = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle POST - Process check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_checkin'])) {
    $reservationId = intval($_POST['reservation_id'] ?? 0);
    $idVerified = isset($_POST['id_verified']) ? 1 : 0;
    $checkinTime = $_POST['checkin_time'] ?? date('Y-m-d H:i:s');
    $notes = sanitize($_POST['notes'] ?? '');

    // Verify reservation exists and is confirmed
    $stmt = $pdo->prepare("SELECT r.*, rm.id AS room_id FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.id
        WHERE r.id = ? AND r.status = ?");
    $stmt->execute([$reservationId, RES_CONFIRMED]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($reservation) {
        try {
            $pdo->beginTransaction();

            // INSERT check_ins
            $stmt = $pdo->prepare("INSERT INTO check_ins (reservation_id, room_id, guest_id, actual_check_in, status, processed_by, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $reservation['id'],
                $reservation['room_id'],
                $reservation['guest_id'],
                $checkinTime,
                CHECKIN_ACTIVE,
                $_SESSION['user_id'],
                $notes
            ]);

            // UPDATE reservations.status = checked_in
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([RES_CHECKED_IN, $reservationId]);

            // UPDATE rooms.status = occupied
            $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->execute([ROOM_OCCUPIED, $reservation['room_id']]);

            // Log activity
            logActivity($pdo, $_SESSION['user_id'], 'checkin', 'checkinout',
                'Checked in reservation ID: ' . $reservationId);

            $pdo->commit();
            setFlash('success', t('checkin_success'));
            redirect('checkin.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', t('operation_failed'));
            redirect('checkin.php');
        }
    } else {
        setFlash('error', t('operation_failed'));
        redirect('checkin.php');
    }
}

$page_title = t('checkin_title');
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-box-arrow-in-right me-2"></i><?= t('checkin_title') ?></h4>
</div>
<?php
// Check-in statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE status = '" . RES_CONFIRMED . "'");
$confirmedCount = (int)$stmt->fetch()['total'];
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM check_ins WHERE date(actual_check_in) = ?");
$stmt->execute([date('Y-m-d')]);
$todayCheckins = (int)$stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM check_ins WHERE status = '" . CHECKIN_ACTIVE . "'");
$activeCheckins = (int)$stmt->fetch()['total'];
?>
<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Confirmed Reservations</h6>
                        <h3><?= $confirmedCount ?></h3>
                    </div>
                    <div class="stat-icon bg-info"><i class="bi bi-calendar-check"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Today's Check-ins</h6>
                        <h3><?= $todayCheckins ?></h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="bi bi-box-arrow-in-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Active Guests</h6>
                        <h3><?= $activeCheckins ?></h3>
                    </div>
                    <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Search Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-search me-2"></i><?= t('search_reservation') ?>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control form-control-lg"
                            placeholder="<?= t('search_by_code') ?>"
                            value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-search me-2"></i><?= t('search') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php 
        // Show all confirmed reservations when no search/selection
        if (!$selectedReservation && empty($searchResults) && empty($searchTerm)):
            $stmt = $pdo->query("
                SELECT r.*, g.first_name AS guest_first, g.last_name AS guest_last,
                    rm.room_number, rt.name AS room_type_name, rt.base_price
                FROM reservations r
                LEFT JOIN guests g ON r.guest_id = g.id
                LEFT JOIN rooms rm ON r.room_id = rm.id
                LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                WHERE r.status = '" . RES_CONFIRMED . "'
                ORDER BY r.check_in_date ASC
                LIMIT 20
            ");
            $allConfirmed = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($allConfirmed)):
        ?>
        <!-- All Confirmed Reservations -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-check me-2"></i>Confirmed Reservations Awaiting Check-in</span>
                <span class="badge bg-primary"><?= count($allConfirmed) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= t('reservation_code') ?></th>
                                <th><?= t('guest_name') ?></th>
                                <th><?= t('room') ?></th>
                                <th><?= t('check_in_date') ?></th>
                                <th><?= t('check_out_date') ?></th>
                                <th><?= t('total_amount') ?></th>
                                <th><?= t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allConfirmed as $r): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($r['reservation_code']) ?></td>
                                <td><?= htmlspecialchars($r['guest_first'] . ' ' . $r['guest_last']) ?></td>
                                <td>
                                    <?= htmlspecialchars($r['room_number']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($r['room_type_name'] ?? '') ?>)</small>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['check_in_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($r['check_out_date'])) ?></td>
                                <td class="fw-semibold"><?= formatCurrency($r['total_amount']) ?></td>
                                <td>
                                    <a href="?reservation_id=<?= $r['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>Check In
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php 
            endif;

            // Show today's check-ins
            $stmt = $pdo->query("
                SELECT ci.*, g.first_name AS guest_first, g.last_name AS guest_last,
                    rm.room_number, rt.name AS room_type_name, rt.base_price
                FROM check_ins ci
                LEFT JOIN guests g ON ci.guest_id = g.id
                LEFT JOIN rooms rm ON ci.room_id = rm.id
                LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                WHERE date(ci.actual_check_in) = '" . date('Y-m-d') . "'
                ORDER BY ci.actual_check_in DESC
            ");
            $todayCheckinsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($todayCheckinsList)):
        ?>
        <!-- Today's Check-ins -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-success text-white">
                <span><i class="bi bi-check-circle me-2"></i>Today's Check-ins</span>
                <span class="badge bg-light text-dark"><?= count($todayCheckinsList) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room</th>
                                <th>Guest Name</th>
                                <th>Check-in Time</th>
                                <th>Room Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayCheckinsList as $tc): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($tc['room_number']) ?></td>
                                <td><?= htmlspecialchars($tc['guest_first'] . ' ' . $tc['guest_last']) ?></td>
                                <td><?= date('h:i A', strtotime($tc['actual_check_in'])) ?></td>
                                <td><?= htmlspecialchars($tc['room_type_name'] ?? '') ?></td>
                                <td><span class="badge bg-success">Checked In</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php 
            endif;
        endif; 
        ?>

        <?php if ($selectedReservation): ?>
        <!-- Check-in Confirmation Form -->
        <form method="POST" action="checkin.php">
            <input type="hidden" name="reservation_id" value="<?= $selectedReservation['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Reservation Details (Read-only) -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-calendar-check me-2"></i><?= t('reservation_details') ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-end" style="width:40%"><?= t('reservation_code') ?>:</th>
                                    <td class="fw-semibold"><?= htmlspecialchars($selectedReservation['reservation_code']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('guest_name') ?>:</th>
                                    <td><?= htmlspecialchars($selectedReservation['guest_first'] . ' ' . $selectedReservation['guest_last']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('phone') ?>:</th>
                                    <td><?= htmlspecialchars($selectedReservation['guest_phone'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('email') ?>:</th>
                                    <td><?= htmlspecialchars($selectedReservation['guest_email'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('nationality') ?>:</th>
                                    <td><?= htmlspecialchars($selectedReservation['nationality'] ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-end" style="width:40%"><?= t('room') ?>:</th>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($selectedReservation['room_number']) ?>
                                        <small class="text-muted">(<?= htmlspecialchars($selectedReservation['room_type_name'] ?? '') ?>)</small>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('price_per_night') ?>:</th>
                                    <td><?= formatCurrency($selectedReservation['base_price']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('check_in_date') ?>:</th>
                                    <td><?= date('M d, Y', strtotime($selectedReservation['check_in_date'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('check_out_date') ?>:</th>
                                    <td><?= date('M d, Y', strtotime($selectedReservation['check_out_date'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('total_nights') ?>:</th>
                                    <td>
                                        <?php
                                        $nights = daysBetween($selectedReservation['check_in_date'], $selectedReservation['check_out_date']);
                                        echo $nights . ' ' . t('nights');
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('total_amount') ?>:</th>
                                    <td class="fw-bold text-primary"><?= formatCurrency($selectedReservation['total_amount']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($selectedReservation['special_requests'])): ?>
                    <div class="alert alert-info">
                        <strong><?= t('special_requests') ?>:</strong>
                        <?= htmlspecialchars($selectedReservation['special_requests']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Identity Verification -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-shield-check me-2"></i><?= t('verify_identity') ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label"><?= t('id_type') ?></label>
                            <input type="text" class="form-control" readonly
                                value="<?= htmlspecialchars($selectedReservation['id_type'] ?? '-') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= t('id_number') ?></label>
                            <input type="text" class="form-control" readonly
                                value="<?= htmlspecialchars($selectedReservation['id_number'] ?? '-') ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="id_verified" id="idVerified" required>
                                <label class="form-check-label fw-bold" for="idVerified"><?= t('verify_identity') ?></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-in Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-clock me-2"></i><?= t('confirm_checkin') ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><?= t('actual_checkin_time') ?></label>
                            <input type="datetime-local" name="checkin_time" class="form-control"
                                value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= t('notes') ?></label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="<?= t('notes') ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-between">
                <a href="checkin.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i><?= t('back') ?>
                </a>
                <button type="submit" name="confirm_checkin" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle me-2"></i><?= t('confirm_checkin') ?>
                </button>
            </div>
        </form>

        <?php elseif (!empty($searchResults)): ?>
        <!-- Search Results -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list me-2"></i><?= t('search') ?> (<?= count($searchResults) ?> <?= t('records') ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= t('reservation_code') ?></th>
                                <th><?= t('guest_name') ?></th>
                                <th><?= t('room') ?></th>
                                <th><?= t('check_in_date') ?></th>
                                <th><?= t('check_out_date') ?></th>
                                <th><?= t('total_amount') ?></th>
                                <th><?= t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $r): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($r['reservation_code']) ?></td>
                                <td><?= htmlspecialchars($r['guest_first'] . ' ' . $r['guest_last']) ?></td>
                                <td>
                                    <?= htmlspecialchars($r['room_number']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($r['room_type_name'] ?? '') ?>)</small>
                                </td>
                                <td><?= date('M d, Y', strtotime($r['check_in_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($r['check_out_date'])) ?></td>
                                <td class="fw-semibold"><?= formatCurrency($r['total_amount']) ?></td>
                                <td>
                                    <a href="?reservation_id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-box-arrow-in-right me-1"></i><?= t('confirm_checkin') ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif (isset($_GET['search']) && empty($searchResults)): ?>
        <div class="alert alert-warning text-center">
            <i class="bi bi-exclamation-triangle me-2"></i><?= t('no_results') ?>
        </div>
        <?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
