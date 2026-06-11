<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('guest_details');
$active_page = 'guests';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/guests/index.php'); }

$reservations = $pdo->prepare("SELECT r.*, rm.room_number FROM reservations r JOIN rooms rm ON r.room_id = rm.id WHERE r.guest_id = ? ORDER BY r.created_at DESC");
$reservations->execute([$id]);
$resList = $reservations->fetchAll();

$stays = $pdo->prepare("SELECT ci.*, rm.room_number FROM check_ins ci JOIN rooms rm ON ci.room_id = rm.id WHERE ci.guest_id = ? ORDER BY ci.actual_check_in DESC");
$stays->execute([$id]);
$stayList = $stays->fetchAll();

$bills = $pdo->prepare("SELECT * FROM bills WHERE guest_id = ? ORDER BY created_at DESC");
$bills->execute([$id]);
$billList = $bills->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person"></i> <?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?>
        <?php if ($guest['vip_status']): ?><i class="bi bi-star-fill vip-star"></i><?php endif; ?>
    </h4>
    <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="card mb-4"><div class="card-body">
    <div class="row">
        <div class="col-md-3"><div class="detail-label"><?php echo t('phone'); ?></div><div class="detail-value"><?php echo htmlspecialchars($guest['phone'] ?? '-'); ?></div></div>
        <div class="col-md-3"><div class="detail-label"><?php echo t('email'); ?></div><div class="detail-value"><?php echo htmlspecialchars($guest['email'] ?? '-'); ?></div></div>
        <div class="col-md-3"><div class="detail-label"><?php echo t('nationality'); ?></div><div class="detail-value"><?php echo htmlspecialchars($guest['nationality'] ?? '-'); ?></div></div>
        <div class="col-md-3"><div class="detail-label"><?php echo t('id_type'); ?></div><div class="detail-value"><?php echo htmlspecialchars(($guest['id_type'] ?? '-') . ' ' . ($guest['id_number'] ?? '')); ?></div></div>
    </div>
</div></div>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#reservations"><?php echo t('reservation_history'); ?></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#stays"><?php echo t('stay_history'); ?></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bills"><?php echo t('billing_history'); ?></a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="reservations">
        <div class="card"><div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th><?php echo t('reservation_code'); ?></th><th><?php echo t('room_number'); ?></th><th><?php echo t('check_in_date'); ?></th><th><?php echo t('check_out_date'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('total_amount'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($resList)): ?>
                <tr><td colspan="6" class="text-center py-3 text-muted"><?php echo t('no_data'); ?></td></tr>
                <?php else: foreach ($resList as $r): ?>
                <tr>
                    <td><a href="<?php echo $baseUrl; ?>/modules/reservations/view.php?id=<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['reservation_code']); ?></a></td>
                    <td><?php echo htmlspecialchars($r['room_number']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($r['check_in_date'])); ?></td>
                    <td><?php echo date('M d, Y', strtotime($r['check_out_date'])); ?></td>
                    <td><span class="badge badge-status badge-<?php echo $r['status']; ?>"><?php echo t('status_' . str_replace('-', '_', $r['status'])); ?></span></td>
                    <td><?php echo formatCurrency($r['total_amount']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="tab-pane fade" id="stays">
        <div class="card"><div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th><?php echo t('room_number'); ?></th><th><?php echo t('actual_checkin_time'); ?></th><th><?php echo t('actual_checkout_time'); ?></th><th><?php echo t('status'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($stayList)): ?>
                <tr><td colspan="4" class="text-center py-3 text-muted"><?php echo t('no_data'); ?></td></tr>
                <?php else: foreach ($stayList as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['room_number']); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($s['actual_check_in'])); ?></td>
                    <td><?php echo $s['actual_check_out'] ? date('M d, Y H:i', strtotime($s['actual_check_out'])) : '-'; ?></td>
                    <td><span class="badge badge-status <?php echo $s['status'] === 'active' ? 'badge-available' : 'badge-checked-out'; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="tab-pane fade" id="bills">
        <div class="card"><div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th><?php echo t('bill_number'); ?></th><th><?php echo t('total_amount'); ?></th><th><?php echo t('payment_status'); ?></th><th><?php echo t('date'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($billList)): ?>
                <tr><td colspan="4" class="text-center py-3 text-muted"><?php echo t('no_data'); ?></td></tr>
                <?php else: foreach ($billList as $b): ?>
                <tr>
                    <td><a href="<?php echo $baseUrl; ?>/modules/billing/view.php?id=<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bill_number']); ?></a></td>
                    <td><?php echo formatCurrency($b['total_amount']); ?></td>
                    <td><span class="badge badge-status badge-<?php echo $b['status']; ?>"><?php echo t('status_' . $b['status']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
