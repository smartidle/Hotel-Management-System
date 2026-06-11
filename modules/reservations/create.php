<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$active_page = 'reservations';
$module_js = 'reservations';

// Fetch all guests for dropdown
$guestStmt = $pdo->prepare("SELECT id, first_name, last_name, phone, email FROM guests ORDER BY first_name, last_name");
$guestStmt->execute();
$guests = $guestStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all rooms with types for initial dropdown
$roomStmt = $pdo->prepare("SELECT rm.id, rm.room_number, rt.name AS type_name, rt.base_price
    FROM rooms rm
    LEFT JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE rm.status = ?
    ORDER BY rm.room_number");
$roomStmt->execute(['available']);
$rooms = $roomStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = intval($_POST['guest_id'] ?? 0);
    $room_id = intval($_POST['room_id'] ?? 0);
    $check_in_date = trim($_POST['check_in_date'] ?? '');
    $check_out_date = trim($_POST['check_out_date'] ?? '');
    $num_guests = intval($_POST['num_guests'] ?? 1);
    $special_requests = trim($_POST['special_requests'] ?? '');

    $errors = [];

    if ($guest_id <= 0) {
        $errors[] = t('select_guest');
    }
    if ($room_id <= 0) {
        $errors[] = t('select_room');
    }
    if (empty($check_in_date)) {
        $errors[] = t('check_in_date') . ' - ' . t('required_field');
    }
    if (empty($check_out_date)) {
        $errors[] = t('check_out_date') . ' - ' . t('required_field');
    }

    // Validate dates
    if (!empty($check_in_date) && !empty($check_out_date)) {
        $ci = new DateTime($check_in_date);
        $co = new DateTime($check_out_date);
        $today = new DateTime('today');

        if ($ci < $today) {
            $errors[] = t('check_in_date') . ' must be today or later.';
        }
        if ($co <= $ci) {
            $errors[] = t('check_out_date') . ' must be after ' . t('check_in_date') . '.';
        }
    }

    if (empty($errors)) {
        try {
            // Calculate total amount = nights * price per night
            $ci = new DateTime($check_in_date);
            $co = new DateTime($check_out_date);
            $nights = $ci->diff($co)->days;

            // Get room price
            $priceStmt = $pdo->prepare("SELECT rt.base_price FROM rooms rm
                LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                WHERE rm.id = ?");
            $priceStmt->execute([$room_id]);
            $roomPrice = $priceStmt->fetchColumn();
            $total_amount = $nights * $roomPrice;

            // Generate reservation code
            $reservation_code = generateCode('RES', $pdo, 'reservations', 'reservation_code');

            // Insert reservation
            $insertStmt = $pdo->prepare("INSERT INTO reservations
                (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, special_requests, total_amount, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $insertStmt->execute([
                $reservation_code,
                $guest_id,
                $room_id,
                $check_in_date,
                $check_out_date,
                $num_guests,
                $special_requests,
                $total_amount,
                $_SESSION['user_id'] ?? null
            ]);

            // Log activity
            logActivity($pdo, $_SESSION['user_id'] ?? null, 'create', 'reservations',
                "Created reservation {$reservation_code}");

            setFlash('success', t('reservation_created'));
            redirect('view.php?id=' . $pdo->lastInsertId());
        } catch (Exception $e) {
            $errors[] = t('operation_failed') . ' ' . $e->getMessage();
        }
    }
}

$page_title = t('add_reservation');

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-calendar-plus me-2"></i><?= t('add_reservation') ?></h4>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?= t('back') ?>
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i><strong>Please fix the following errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

        <form method="POST" action="create.php" id="reservationForm">
            <div class="row g-4">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-person me-2 text-primary"></i><?= t('guest_information') ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= t('search_guest') ?> <small class="text-muted">(<?= t('name') ?>/<?= t('phone') ?>)</small></label>
                                    <input type="text" id="guestSearch" class="form-control" placeholder="<?= t('search_guest') ?>...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= t('select_guest') ?> <span class="text-danger">*</span></label>
                                    <select name="guest_id" id="guest_id" class="form-select" required>
                                        <option value="">-- <?= t('select_guest') ?> --</option>
                                        <?php foreach ($guests as $g): ?>
                                        <option value="<?= $g['id'] ?>" <?= (isset($_POST['guest_id']) && $_POST['guest_id'] == $g['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($g['first_name'] . ' ' . $g['last_name']) ?>
                                            <?= $g['phone'] ? '(' . htmlspecialchars($g['phone']) . ')' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickGuestModal">
                                    <i class="bi bi-plus-circle me-1"></i><?= t('add_guest') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i><?= t('reservation_details') ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label"><?= t('check_in_date') ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="check_in_date" id="check_in_date" class="form-control"
                                        value="<?= htmlspecialchars($_POST['check_in_date'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= t('check_out_date') ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="check_out_date" id="check_out_date" class="form-control"
                                        value="<?= htmlspecialchars($_POST['check_out_date'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><?= t('select_room') ?> <span class="text-danger">*</span></label>
                                    <select name="room_id" id="room_id" class="form-select" required>
                                        <option value="">-- <?= t('select_room') ?> --</option>
                                        <?php foreach ($rooms as $rm): ?>
                                        <option value="<?= $rm['id'] ?>"
                                            data-price="<?= $rm['base_price'] ?>"
                                            <?= (isset($_POST['room_id']) && $_POST['room_id'] == $rm['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($rm['room_number']) ?> - <?= htmlspecialchars($rm['type_name']) ?> (<?= formatCurrency($rm['base_price']) ?>/<?= t('nights') ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= t('num_guests') ?></label>
                                    <input type="number" name="num_guests" class="form-control" min="1" max="10"
                                        value="<?= htmlspecialchars($_POST['num_guests'] ?? 1) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?= t('total_nights') ?></label>
                                    <input type="text" id="totalNights" class="form-control" readonly value="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label"><?= t('special_requests') ?></label>
                                    <textarea name="special_requests" class="form-control" rows="3"
                                        placeholder="<?= t('special_requests') ?>..."><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm sticky-lg-top" style="top: 80px;">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-receipt me-2"></i><?= t('total_amount') ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <span class="text-muted"><?= t('price_per_night') ?></span>
                                <input type="hidden" id="pricePerNight" value="0">
                                <span id="priceDisplay" class="fw-semibold"><?= formatCurrency(0) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <span class="text-muted"><?= t('total_nights') ?></span>
                                <span id="nightsSummary" class="fw-semibold">0</span>
                            </div>
                            <div class="text-center py-3">
                                <small class="text-muted text-uppercase"><?= t('total_amount') ?></small>
                                <h3 id="totalAmountDisplay" class="text-primary fw-bold mb-0"><?= formatCurrency(0) ?></h3>
                                <input type="hidden" id="totalAmount" name="total_amount" value="0.00">
                                <input type="hidden" id="pricePerNightHidden" value="0">
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-check-circle me-2"></i><?= t('submit') ?>
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="bi bi-x-circle me-1"></i><?= t('cancel') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

<!-- Quick Create Guest Modal -->
<div class="modal fade" id="quickGuestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= t('add_guest') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickGuestForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= t('first_name') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= t('last_name') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= t('phone') ?></label>
                            <input type="text" name="phone" class="form-control" placeholder="+63 9XX XXX XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= t('email') ?></label>
                            <input type="email" name="email" class="form-control" placeholder="guest@example.com">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('close') ?></button>
                <button type="button" class="btn btn-primary" id="btnQuickCreateGuest">
                    <i class="bi bi-plus-circle me-1"></i><?= t('create_new') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
