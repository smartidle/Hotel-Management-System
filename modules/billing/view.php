<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('bill_details');
$active_page = 'billing';
$module_js = 'billing';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, g.first_name, g.last_name, g.phone, g.email, rm.room_number
    FROM bills b
    JOIN guests g ON b.guest_id = g.id
    JOIN reservations r ON b.reservation_id = r.id
    JOIN rooms rm ON r.room_id = rm.id
    WHERE b.id = ?
");
$stmt->execute([$id]);
$bill = $stmt->fetch();
if (!$bill) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/billing/index.php'); }

$charges = $pdo->prepare("SELECT * FROM extra_charges WHERE bill_id = ?");
$charges->execute([$id]);
$chargeList = $charges->fetchAll();

$payments = $pdo->prepare("SELECT * FROM payments WHERE bill_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$paymentList = $payments->fetchAll();

$totalPaid = array_sum(array_column($paymentList, 'amount'));
$balance = $bill['total_amount'] - $totalPaid;

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header no-print">
    <h4><i class="bi bi-receipt"></i> <?php echo t('bill_details'); ?> - <?php echo htmlspecialchars($bill['bill_number']); ?></h4>
    <div>
        <button id="printBill" class="btn btn-outline-primary"><i class="bi bi-printer"></i> <?php echo t('print_bill'); ?></button>
        <a href="<?php echo $baseUrl; ?>/modules/billing/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
</div>

<!-- Print Header -->
<div class="print-only text-center mb-4">
    <h3><?php echo APP_NAME; ?></h3>
    <p class="mb-0">Bill #: <?php echo htmlspecialchars($bill['bill_number']); ?> | Date: <?php echo date('M d, Y', strtotime($bill['created_at'])); ?></p>
    <hr>
</div>

<!-- Guest & Bill Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h6 class="text-muted mb-2"><?php echo t('guest_information'); ?></h6>
            <div><strong><?php echo htmlspecialchars($bill['first_name'] . ' ' . $bill['last_name']); ?></strong></div>
            <div><?php echo htmlspecialchars($bill['phone']); ?> | <?php echo htmlspecialchars($bill['email'] ?? ''); ?></div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body">
            <h6 class="text-muted mb-2"><?php echo t('bill_details'); ?></h6>
            <div><?php echo t('bill_number'); ?>: <strong><?php echo htmlspecialchars($bill['bill_number']); ?></strong></div>
            <div><?php echo t('room_number'); ?>: <?php echo htmlspecialchars($bill['room_number']); ?></div>
            <div><?php echo t('payment_status'); ?>: <span class="badge badge-status badge-<?php echo $bill['status']; ?>"><?php echo t('status_' . $bill['status']); ?></span></div>
        </div></div>
    </div>
</div>

<!-- Charges -->
<div class="card mb-4">
    <div class="card-header"><?php echo t('room_charges'); ?> & <?php echo t('extra_charges'); ?></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th><?php echo t('description'); ?></th><th class="text-end"><?php echo t('amount'); ?></th></tr></thead>
            <tbody>
                <tr><td><?php echo t('room_charges'); ?></td><td class="text-end"><?php echo formatCurrency($bill['room_charges']); ?></td></tr>
                <?php foreach ($chargeList as $c): ?>
                <tr><td><?php echo htmlspecialchars($c['description']); ?> <small class="text-muted">(<?php echo ucfirst($c['charge_type']); ?>)</small></td><td class="text-end"><?php echo formatCurrency($c['amount']); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="fw-bold">
                <tr><td><?php echo t('extra_charges'); ?> <?php echo t('subtotal'); ?></td><td class="text-end"><?php echo formatCurrency($bill['extra_charges']); ?></td></tr>
                <tr><td><?php echo t('tax'); ?> (12%)</td><td class="text-end"><?php echo formatCurrency($bill['tax_amount']); ?></td></tr>
                <?php if ($bill['discount'] > 0): ?>
                <tr><td><?php echo t('discount'); ?></td><td class="text-end text-danger">-<?php echo formatCurrency($bill['discount']); ?></td></tr>
                <?php endif; ?>
                <tr class="table-primary"><td><?php echo t('total_amount'); ?></td><td class="text-end fs-5"><?php echo formatCurrency($bill['total_amount']); ?></td></tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Payments -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><?php echo t('amount_paid'); ?>: <?php echo formatCurrency($totalPaid); ?> | <?php echo t('balance'); ?>: <?php echo formatCurrency($balance); ?></span>
        <?php if ($bill['status'] !== 'paid'): ?>
        <button class="btn btn-sm btn-success no-print" data-bs-toggle="modal" data-bs-target="#paymentModal"><i class="bi bi-plus-circle"></i> <?php echo t('add_payment'); ?></button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th><?php echo t('date'); ?></th><th><?php echo t('payment_method'); ?></th><th><?php echo t('reference_number'); ?></th><th class="text-end"><?php echo t('amount'); ?></th></tr></thead>
            <tbody>
                <?php if (empty($paymentList)): ?>
                <tr><td colspan="4" class="text-center py-3 text-muted"><?php echo t('no_data'); ?></td></tr>
                <?php else: foreach ($paymentList as $p): ?>
                <tr>
                    <td><?php echo date('M d, Y H:i', strtotime($p['payment_date'])); ?></td>
                    <td><?php echo t($p['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($p['reference_number'] ?? '-'); ?></td>
                    <td class="text-end"><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade no-print" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="paymentForm" method="POST" action="<?php echo $baseUrl; ?>/modules/billing/api.php">
                <input type="hidden" name="action" value="add_payment">
                <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                <div class="modal-header"><h5 class="modal-title"><?php echo t('add_payment'); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('amount'); ?> (<?php echo t('balance'); ?>: <?php echo formatCurrency($balance); ?>)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="0.01" max="<?php echo $balance; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('payment_method'); ?></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash"><?php echo t('cash'); ?></option>
                            <option value="credit_card"><?php echo t('credit_card'); ?></option>
                            <option value="debit_card"><?php echo t('debit_card'); ?></option>
                            <option value="bank_transfer"><?php echo t('bank_transfer'); ?></option>
                            <option value="online"><?php echo t('online'); ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('reference_number'); ?></label>
                        <input type="text" name="reference_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('close'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo t('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
