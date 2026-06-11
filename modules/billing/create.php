<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('create_bill');
$active_page = 'billing';
$module_js = 'billing';
$baseUrl = getBaseUrl();

$reservation_id = (int)($_GET['reservation_id'] ?? 0);

// Get checked-out reservations without bills
$stmt = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, rm.room_number, rt.base_price, rt.name as type_name
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE r.status = 'checked_out'
    AND r.id NOT IN (SELECT reservation_id FROM bills)
    ORDER BY r.check_out_date DESC
");
$reservations = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res_id = (int)($_POST['reservation_id'] ?? 0);
    $room_charges = (float)($_POST['room_charges'] ?? 0);
    $tax_amount = (float)($_POST['tax_amount'] ?? 0);
    $discount = (float)($_POST['discount'] ?? 0);
    $extra_charges = (float)($_POST['extra_charges_total'] ?? 0);
    $total_amount = (float)($_POST['total_amount'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    // Get guest_id
    $gstmt = $pdo->prepare("SELECT guest_id FROM reservations WHERE id = ?");
    $gstmt->execute([$res_id]);
    $guest_id = $gstmt->fetchColumn();

    $bill_number = generateCode('BILL', $pdo, 'bills', 'bill_number');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO bills (bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, discount, total_amount, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$bill_number, $res_id, $guest_id, $room_charges, $extra_charges, $tax_amount, $discount, $total_amount, $notes, $_SESSION['user_id']]);
        $bill_id = $pdo->lastInsertId();

        // Insert extra charges
        $types = $_POST['charge_type'] ?? [];
        $descs = $_POST['charge_desc'] ?? [];
        $amounts = $_POST['charge_amount'] ?? [];
        for ($i = 0; $i < count($types); $i++) {
            if (!empty($amounts[$i]) && $amounts[$i] > 0) {
                $cs = $pdo->prepare("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (?,?,?,?)");
                $cs->execute([$bill_id, sanitize($descs[$i]), (float)$amounts[$i], sanitize($types[$i])]);
            }
        }

        $pdo->commit();
        logActivity($pdo, $_SESSION['user_id'], 'create', 'billing', "Created bill $bill_number");
        setFlash('success', t('bill_created'));
        redirect($baseUrl . '/modules/billing/view.php?id=' . $bill_id);
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('error', t('operation_failed'));
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-receipt"></i> <?php echo t('create_bill'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/billing/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card"><div class="card-body">
<form method="POST" action="">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Reservation *</label>
            <select name="reservation_id" id="reservation_id" class="form-select" required>
                <option value="">-- Select Reservation --</option>
                <?php foreach ($reservations as $r): ?>
                <option value="<?php echo $r['id']; ?>" data-price="<?php echo $r['base_price']; ?>" data-checkin="<?php echo $r['check_in_date']; ?>" data-checkout="<?php echo $r['check_out_date']; ?>" <?php echo $reservation_id == $r['id'] ? 'selected' : ''; ?>>
                    <?php echo $r['reservation_code']; ?> - <?php echo $r['first_name'] . ' ' . $r['last_name']; ?> (Room <?php echo $r['room_number']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('room_charges'); ?></label>
            <input type="number" name="room_charges" id="room_charges" class="form-control" step="0.01" value="0" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('discount'); ?></label>
            <input type="number" name="discount" id="discount" class="form-control" step="0.01" value="0" min="0">
        </div>
    </div>

    <hr class="my-3">
    <h6><?php echo t('extra_charges'); ?></h6>
    <div id="extraChargesContainer"></div>
    <button type="button" id="addChargeRow" class="btn btn-sm btn-outline-primary mb-3"><i class="bi bi-plus"></i> <?php echo t('add_charge'); ?></button>

    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label"><?php echo t('extra_charges'); ?> <?php echo t('subtotal'); ?></label>
            <input type="number" name="extra_charges_total" id="extra_charges_total" class="form-control" value="0" step="0.01" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('tax'); ?> (12%)</label>
            <input type="number" name="tax_amount" id="tax_amount" class="form-control" value="0" step="0.01" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('total_amount'); ?></label>
            <input type="number" name="total_amount" id="total_amount" class="form-control fw-bold" value="0" step="0.01" readonly>
        </div>
        <div class="col-md-3">
            <label class="form-label"><?php echo t('notes'); ?></label>
            <input type="text" name="notes" class="form-control">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?php echo t('generate_bill'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/billing/index.php" class="btn btn-outline-secondary"><?php echo t('cancel'); ?></a>
        </div>
    </div>
</form>
</div></div>

<script>
// Auto-calculate room charges when reservation selected
document.getElementById('reservation_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.price) {
        const nights = Math.ceil((new Date(opt.dataset.checkout) - new Date(opt.dataset.checkin)) / 86400000);
        document.getElementById('room_charges').value = (nights * parseFloat(opt.dataset.price)).toFixed(2);
        recalcBill();
    }
});
document.getElementById('discount').addEventListener('input', function() { recalcBill(); });

function recalcBill() {
    const room = parseFloat(document.getElementById('room_charges').value || 0);
    let extra = 0;
    document.querySelectorAll('.charge-amount').forEach(i => extra += parseFloat(i.value || 0));
    const discount = parseFloat(document.getElementById('discount').value || 0);
    const subtotal = room + extra - discount;
    const tax = subtotal * 0.12;
    document.getElementById('extra_charges_total').value = extra.toFixed(2);
    document.getElementById('tax_amount').value = tax.toFixed(2);
    document.getElementById('total_amount').value = (subtotal + tax).toFixed(2);
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
