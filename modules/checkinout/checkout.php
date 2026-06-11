<?php
/**
 * Check-out Page
 * Search active check-ins and process check-out with billing
 */
require_once __DIR__ . '/../../includes/auth_check.php';

$active_page = 'checkout';
$module_js = 'checkinout';

$searchResults = [];
$selectedCheckin = null;
$searchTerm = '';

// Handle search
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = trim($_GET['search']);
    $like = '%' . $searchTerm . '%';
    $stmt = $pdo->prepare("
        SELECT ci.*, r.reservation_code, r.check_in_date, r.check_out_date, r.total_amount AS reservation_total,
            g.first_name AS guest_first, g.last_name AS guest_last,
            rm.room_number, rt.name AS room_type_name, rt.base_price
        FROM check_ins ci
        LEFT JOIN reservations r ON ci.reservation_id = r.id
        LEFT JOIN guests g ON ci.guest_id = g.id
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE ci.status = ?
            AND (rm.room_number LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)
        ORDER BY ci.actual_check_in ASC
    ");
    $stmt->execute([CHECKIN_ACTIVE, $like, $like, $like]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle selecting a check-in record
if (isset($_GET['checkin_id']) && intval($_GET['checkin_id']) > 0) {
    $checkinId = intval($_GET['checkin_id']);
    $stmt = $pdo->prepare("
        SELECT ci.*, r.reservation_code, r.check_in_date, r.check_out_date, r.total_amount AS reservation_total,
            r.special_requests,
            g.first_name AS guest_first, g.last_name AS guest_last,
            rm.room_number, rt.name AS room_type_name, rt.base_price
        FROM check_ins ci
        LEFT JOIN reservations r ON ci.reservation_id = r.id
        LEFT JOIN guests g ON ci.guest_id = g.id
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE ci.id = ? AND ci.status = ?
    ");
    $stmt->execute([$checkinId, CHECKIN_ACTIVE]);
    $selectedCheckin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate room charges
    if ($selectedCheckin) {
        $checkinTime = new DateTime($selectedCheckin['actual_check_in']);
        $checkoutTime = new DateTime();
        $nights = max(1, $checkinTime->diff($checkoutTime)->days);
        $roomCharges = $nights * (float)$selectedCheckin['base_price'];
        $selectedCheckin['calculated_nights'] = $nights;
        $selectedCheckin['calculated_room_charges'] = $roomCharges;
    }
}

// Handle POST - Process check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_checkout'])) {
    $checkinId = intval($_POST['checkin_id'] ?? 0);
    $checkoutTime = $_POST['checkout_time'] ?? date('Y-m-d H:i:s');
    $notes = sanitize($_POST['notes'] ?? '');
    $roomCharges = floatval($_POST['room_charges'] ?? 0);
    $extraChargesTotal = floatval($_POST['extra_charges_total'] ?? 0);
    $taxAmount = floatval($_POST['tax_amount'] ?? 0);
    $totalAmount = floatval($_POST['total_amount'] ?? 0);

    // Extra charges arrays
    $chargeDescriptions = $_POST['charge_description'] ?? [];
    $chargeAmounts = $_POST['charge_amount'] ?? [];
    $chargeTypes = $_POST['charge_type'] ?? [];

    // Verify check-in exists and is active
    $stmt = $pdo->prepare("
        SELECT ci.*, r.id AS reservation_id, r.guest_id, rm.id AS room_id
        FROM check_ins ci
        LEFT JOIN reservations r ON ci.reservation_id = r.id
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        WHERE ci.id = ? AND ci.status = ?
    ");
    $stmt->execute([$checkinId, CHECKIN_ACTIVE]);
    $checkin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($checkin) {
        try {
            $pdo->beginTransaction();

            // UPDATE check_ins
            $stmt = $pdo->prepare("UPDATE check_ins SET actual_check_out = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$checkoutTime, CHECKIN_COMPLETED, $notes, $checkinId]);

            // UPDATE reservations.status = checked_out
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([RES_CHECKED_OUT, $checkin['reservation_id']]);

            // UPDATE rooms.status = available
            $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
            $stmt->execute([ROOM_AVAILABLE, $checkin['room_id']]);

            // Generate bill number
            $billNumber = generateCode('BILL', $pdo, 'bills', 'bill_number');

            // INSERT bills
            $stmt = $pdo->prepare("INSERT INTO bills (bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, total_amount, status, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $billNumber,
                $checkin['reservation_id'],
                $checkin['guest_id'],
                $roomCharges,
                $extraChargesTotal,
                $taxAmount,
                $totalAmount,
                BILL_UNPAID,
                $notes,
                $_SESSION['user_id']
            ]);
            $billId = $pdo->lastInsertId();

            // INSERT extra_charges
            for ($i = 0; $i < count($chargeDescriptions); $i++) {
                if (!empty($chargeDescriptions[$i]) && floatval($chargeAmounts[$i]) > 0) {
                    $stmt = $pdo->prepare("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $billId,
                        sanitize($chargeDescriptions[$i]),
                        floatval($chargeAmounts[$i]),
                        $chargeTypes[$i] ?? CHARGE_OTHER
                    ]);
                }
            }

            // Log activity
            logActivity($pdo, $_SESSION['user_id'], 'checkout', 'checkinout',
                'Checked out check-in ID: ' . $checkinId . ', Bill: ' . $billNumber);

            $pdo->commit();
            setFlash('success', t('checkout_success'));
            redirect('checkout.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', t('operation_failed'));
            redirect('checkout.php');
        }
    } else {
        setFlash('error', t('operation_failed'));
        redirect('checkout.php');
    }
}

$page_title = t('checkout_title');
include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-box-arrow-right me-2"></i><?= t('checkout_title') ?></h4>
</div>
<?php
// Checkout statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM check_ins WHERE status = '" . CHECKIN_ACTIVE . "'");
$activeGuests = (int)$stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
$occupiedRooms = (int)$stmt->fetch()['total'];
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE date(payment_date) = ?");
$stmt->execute([date('Y-m-d')]);
$todayRevenue = (float)$stmt->fetch()['total'];
?>
<!-- Stats Summary -->
<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Active Guests</h6>
                        <h3><?= $activeGuests ?></h3>
                    </div>
                    <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Occupied Rooms</h6>
                        <h3><?= $occupiedRooms ?></h3>
                    </div>
                    <div class="stat-icon bg-warning"><i class="bi bi-door-closed"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Today's Revenue</h6>
                        <h3><?= formatCurrency($todayRevenue) ?></h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="bi bi-currency-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Search Section -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-search me-2"></i><?= t('current_checkins') ?>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" name="search" class="form-control form-control-lg"
                            placeholder="<?= t('search_room_guest') ?>"
                            value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-warning btn-lg w-100">
                            <i class="bi bi-search me-2"></i><?= t('search') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php 
        // Show all active check-ins when no search/selection
        if (!$selectedCheckin && empty($searchResults) && empty($searchTerm)):
            $stmt = $pdo->query("
                SELECT ci.*, r.reservation_code, r.check_in_date, r.check_out_date,
                    g.first_name AS guest_first, g.last_name AS guest_last,
                    rm.room_number, rt.name AS room_type_name, rt.base_price
                FROM check_ins ci
                LEFT JOIN reservations r ON ci.reservation_id = r.id
                LEFT JOIN guests g ON ci.guest_id = g.id
                LEFT JOIN rooms rm ON ci.room_id = rm.id
                LEFT JOIN room_types rt ON rm.room_type_id = rt.id
                WHERE ci.status = '" . CHECKIN_ACTIVE . "'
                ORDER BY ci.actual_check_in ASC
            ");
            $allActive = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($allActive)):
        ?>
        <!-- All Active Check-ins -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i>Current Guests</span>
                <span class="badge bg-warning text-dark"><?= count($allActive) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= t('room') ?></th>
                                <th><?= t('guest_name') ?></th>
                                <th><?= t('reservation_code') ?></th>
                                <th><?= t('actual_checkin_time') ?></th>
                                <th><?= t('stay_duration') ?></th>
                                <th><?= t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allActive as $r):
                                $checkinTime = new DateTime($r['actual_check_in']);
                                $nights = max(1, $checkinTime->diff(new DateTime())->days);
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($r['room_number']) ?></td>
                                <td><?= htmlspecialchars($r['guest_first'] . ' ' . $r['guest_last']) ?></td>
                                <td><?= htmlspecialchars($r['reservation_code']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($r['actual_check_in'])) ?></td>
                                <td><?= $nights ?> <?= t('nights') ?></td>
                                <td>
                                    <a href="?checkin_id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-box-arrow-right me-1"></i>Check Out
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
        endif; 
        ?>

        <?php if ($selectedCheckin): ?>
        <!-- Check-out Settlement Form -->
        <form method="POST" action="checkout.php" id="checkoutForm">
            <input type="hidden" name="checkin_id" value="<?= $selectedCheckin['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <!-- Stay Details -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-info-circle me-2"></i><?= t('details') ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-end" style="width:40%"><?= t('guest_name') ?>:</th>
                                    <td class="fw-semibold"><?= htmlspecialchars($selectedCheckin['guest_first'] . ' ' . $selectedCheckin['guest_last']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('reservation_code') ?>:</th>
                                    <td><?= htmlspecialchars($selectedCheckin['reservation_code']) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('room') ?>:</th>
                                    <td>
                                        <?= htmlspecialchars($selectedCheckin['room_number']) ?>
                                        <small class="text-muted">(<?= htmlspecialchars($selectedCheckin['room_type_name'] ?? '') ?>)</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th class="text-end" style="width:40%"><?= t('actual_checkin_time') ?>:</th>
                                    <td><?= date('M d, Y H:i', strtotime($selectedCheckin['actual_check_in'])) ?></td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('stay_duration') ?>:</th>
                                    <td class="fw-semibold">
                                        <?= $selectedCheckin['calculated_nights'] ?> <?= t('nights') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="text-end"><?= t('price_per_night') ?>:</th>
                                    <td><?= formatCurrency($selectedCheckin['base_price']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Billing Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-receipt me-2"></i><?= t('bill_details') ?>
                </div>
                <div class="card-body">
                    <!-- Room Charges -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="form-control-plaintext fw-semibold">
                                <?= t('room_charges') ?>
                                (<?= $selectedCheckin['calculated_nights'] ?> <?= t('nights') ?>
                                x <?= formatCurrency($selectedCheckin['base_price']) ?>)
                            </div>
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control text-end fw-bold" id="roomCharges" readonly
                                name="room_charges"
                                value="<?= number_format($selectedCheckin['calculated_room_charges'], 2, '.', '') ?>">
                        </div>
                    </div>

                    <hr>

                    <!-- Extra Charges (Dynamic) -->
                    <h6 class="mb-3"><i class="bi bi-plus-circle me-1"></i><?= t('extra_charges') ?></h6>
                    <div id="extraChargesContainer">
                        <!-- Extra charge rows will be added here -->
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addChargeBtn">
                        <i class="bi bi-plus me-1"></i><?= t('add_charge') ?>
                    </button>

                    <input type="hidden" name="extra_charges_total" id="extraChargesTotal" value="0">

                    <hr>

                    <!-- Totals -->
                    <div class="row mb-2">
                        <div class="col-md-8 text-end">
                            <strong><?= t('subtotal') ?>:</strong>
                        </div>
                        <div class="col-md-4 text-end">
                            <strong id="subtotalDisplay"><?= formatCurrency($selectedCheckin['calculated_room_charges']) ?></strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-8 text-end">
                            <?= t('tax') ?> (<?= TAX_RATE * 100 ?>%):
                        </div>
                        <div class="col-md-4 text-end">
                            <input type="hidden" name="tax_amount" id="taxAmount"
                                value="<?= number_format($selectedCheckin['calculated_room_charges'] * TAX_RATE, 2, '.', '') ?>">
                            <span id="taxDisplay"><?= formatCurrency($selectedCheckin['calculated_room_charges'] * TAX_RATE) ?></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8 text-end">
                            <h5 class="mb-0"><?= t('total') ?>:</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <input type="hidden" name="total_amount" id="totalAmount"
                                value="<?= number_format($selectedCheckin['calculated_room_charges'] * (1 + TAX_RATE), 2, '.', '') ?>">
                            <h4 class="mb-0 text-primary fw-bold" id="totalDisplay">
                                <?= formatCurrency($selectedCheckin['calculated_room_charges'] * (1 + TAX_RATE)) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Check-out Time and Notes -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label"><?= t('actual_checkout_time') ?></label>
                            <input type="datetime-local" name="checkout_time" class="form-control"
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
                <a href="checkout.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i><?= t('back') ?>
                </a>
                <button type="submit" name="confirm_checkout" class="btn btn-danger btn-lg">
                    <i class="bi bi-box-arrow-right me-2"></i><?= t('confirm_checkout') ?>
                </button>
            </div>
        </form>

        <?php elseif (!empty($searchResults)): ?>
        <!-- Search Results -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list me-2"></i><?= t('current_checkins') ?> (<?= count($searchResults) ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= t('room') ?></th>
                                <th><?= t('guest_name') ?></th>
                                <th><?= t('reservation_code') ?></th>
                                <th><?= t('actual_checkin_time') ?></th>
                                <th><?= t('stay_duration') ?></th>
                                <th><?= t('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $r):
                                $checkinTime = new DateTime($r['actual_check_in']);
                                $nights = max(1, $checkinTime->diff(new DateTime())->days);
                            ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($r['room_number']) ?></td>
                                <td><?= htmlspecialchars($r['guest_first'] . ' ' . $r['guest_last']) ?></td>
                                <td><?= htmlspecialchars($r['reservation_code']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($r['actual_check_in'])) ?></td>
                                <td><?= $nights ?> <?= t('nights') ?></td>
                                <td>
                                    <a href="?checkin_id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-box-arrow-right me-1"></i><?= t('confirm_checkout') ?>
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

<!-- Template for extra charge row (hidden) -->
<template id="chargeRowTemplate">
    <div class="row mb-2 charge-row">
        <div class="col-md-3">
            <select name="charge_type[]" class="form-select form-select-sm">
                <option value="minibar"><?= t('minibar') ?></option>
                <option value="laundry"><?= t('laundry') ?></option>
                <option value="room_service"><?= t('room_service') ?></option>
                <option value="phone"><?= t('phone_charges') ?></option>
                <option value="other" selected><?= t('other') ?></option>
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="charge_description[]" class="form-control form-control-sm"
                placeholder="<?= t('charge_description') ?>">
        </div>
        <div class="col-md-3">
            <input type="number" name="charge_amount[]" class="form-control form-control-sm charge-amount"
                step="0.01" min="0" placeholder="0.00">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-charge-btn">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('addChargeBtn');
    const container = document.getElementById('extraChargesContainer');
    const template = document.getElementById('chargeRowTemplate');

    if (addBtn && template) {
        addBtn.addEventListener('click', function() {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
            bindChargeEvents();
        });
    }

    function bindChargeEvents() {
        document.querySelectorAll('.remove-charge-btn').forEach(function(btn) {
            btn.onclick = function() {
                this.closest('.charge-row').remove();
                recalculate();
            };
        });

        document.querySelectorAll('.charge-amount').forEach(function(input) {
            input.oninput = function() {
                recalculate();
            };
        });
    }

    function recalculate() {
        const roomCharges = parseFloat(document.getElementById('roomCharges').value) || 0;
        let extraTotal = 0;
        document.querySelectorAll('.charge-amount').forEach(function(input) {
            extraTotal += parseFloat(input.value) || 0;
        });

        document.getElementById('extraChargesTotal').value = extraTotal.toFixed(2);

        const subtotal = roomCharges + extraTotal;
        const tax = subtotal * <?= TAX_RATE ?>;
        const total = subtotal + tax;

        document.getElementById('subtotalDisplay').textContent = '<?= CURRENCY_SYMBOL ?>' + subtotal.toFixed(2);
        document.getElementById('taxAmount').value = tax.toFixed(2);
        document.getElementById('taxDisplay').textContent = '<?= CURRENCY_SYMBOL ?>' + tax.toFixed(2);
        document.getElementById('totalAmount').value = total.toFixed(2);
        document.getElementById('totalDisplay').textContent = '<?= CURRENCY_SYMBOL ?>' + total.toFixed(2);
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
