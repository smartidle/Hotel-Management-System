<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('bill_list');
$active_page = 'billing';
$module_js = 'billing';
$baseUrl = getBaseUrl();

$filter_status = $_GET['status'] ?? '';

$where = '';
$params = [];
if ($filter_status) { $where = "WHERE b.status = ?"; $params[] = $filter_status; }

$stmt = $pdo->prepare("
    SELECT b.*, g.first_name, g.last_name, rm.room_number
    FROM bills b
    JOIN guests g ON b.guest_id = g.id
    JOIN reservations r ON b.reservation_id = r.id
    JOIN rooms rm ON r.room_id = rm.id
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bills = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-receipt"></i> <?php echo t('bill_list'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/billing/create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> <?php echo t('create_bill'); ?></a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value=""><?php echo t('all'); ?> <?php echo t('payment_status'); ?></option>
                <option value="unpaid" <?php echo $filter_status==='unpaid'?'selected':''; ?>><?php echo t('status_unpaid'); ?></option>
                <option value="partial" <?php echo $filter_status==='partial'?'selected':''; ?>><?php echo t('status_partial'); ?></option>
                <option value="paid" <?php echo $filter_status==='paid'?'selected':''; ?>><?php echo t('status_paid'); ?></option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> <?php echo t('filter'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/billing/index.php" class="btn btn-sm btn-outline-secondary"><?php echo t('reset'); ?></a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo t('bill_number'); ?></th>
                        <th><?php echo t('guest_name'); ?></th>
                        <th><?php echo t('room_charges'); ?></th>
                        <th><?php echo t('extra_charges'); ?></th>
                        <th><?php echo t('tax'); ?></th>
                        <th><?php echo t('total_amount'); ?></th>
                        <th><?php echo t('payment_status'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted"><?php echo t('no_data'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($bills as $b): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($b['bill_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                        <td><?php echo formatCurrency($b['room_charges']); ?></td>
                        <td><?php echo formatCurrency($b['extra_charges']); ?></td>
                        <td><?php echo formatCurrency($b['tax_amount']); ?></td>
                        <td><strong><?php echo formatCurrency($b['total_amount']); ?></strong></td>
                        <td><span class="badge badge-status badge-<?php echo $b['status']; ?>"><?php echo t('status_' . $b['status']); ?></span></td>
                        <td>
                            <a href="<?php echo $baseUrl; ?>/modules/billing/view.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
