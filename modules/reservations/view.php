<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('reservation_details');
$active_page = 'reservations';
$baseUrl = getBaseUrl();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT r.*, g.first_name as gfirst, g.last_name as glast, g.phone as gphone, g.email as gemail, g.nationality,
           rm.room_number, rt.name as type_name, rt.base_price
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$res = $stmt->fetch();
if (!$res) { setFlash('error', t('no_data')); redirect($baseUrl . '/modules/reservations/index.php'); }

$nights = daysBetween($res['check_in_date'], $res['check_out_date']);

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-calendar-check"></i> <?php echo t('reservation_details'); ?> - <?php echo htmlspecialchars($res['reservation_code']); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/reservations/index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?php echo t('back'); ?></a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-header"><?php echo t('reservation_details'); ?></div><div class="card-body">
            <div class="row">
                <div class="col-md-4"><div class="detail-label"><?php echo t('reservation_code'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['reservation_code']); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('status'); ?></div><div class="detail-value"><span class="badge badge-status badge-<?php echo $res['status']; ?>"><?php echo t('status_' . str_replace('-','_',$res['status'])); ?></span></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('total_nights'); ?></div><div class="detail-value"><?php echo $nights; ?> <?php echo t('nights'); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('check_in_date'); ?></div><div class="detail-value"><?php echo date('M d, Y', strtotime($res['check_in_date'])); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('check_out_date'); ?></div><div class="detail-value"><?php echo date('M d, Y', strtotime($res['check_out_date'])); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('num_guests'); ?></div><div class="detail-value"><?php echo $res['num_guests']; ?></div></div>
                <div class="col-md-6"><div class="detail-label"><?php echo t('room_number'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['room_number']); ?> - <?php echo $res['type_name']; ?> (<?php echo formatCurrency($res['base_price']); ?>/night)</div></div>
                <div class="col-md-6"><div class="detail-label"><?php echo t('total_amount'); ?></div><div class="detail-value fs-5 text-primary"><?php echo formatCurrency($res['total_amount']); ?></div></div>
                <?php if ($res['special_requests']): ?>
                <div class="col-12 mt-2"><div class="detail-label"><?php echo t('special_requests'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['special_requests']); ?></div></div>
                <?php endif; ?>
            </div>
        </div></div>

        <div class="card"><div class="card-header"><?php echo t('guest_information'); ?></div><div class="card-body">
            <div class="row">
                <div class="col-md-4"><div class="detail-label"><?php echo t('name'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['gfirst'] . ' ' . $res['glast']); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('phone'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['gphone']); ?></div></div>
                <div class="col-md-4"><div class="detail-label"><?php echo t('email'); ?></div><div class="detail-value"><?php echo htmlspecialchars($res['gemail']); ?></div></div>
            </div>
        </div></div>
    </div>

    <div class="col-lg-4">
        <div class="card"><div class="card-header"><?php echo t('actions'); ?></div><div class="card-body">
            <?php if (in_array($res['status'], ['pending', 'confirmed'])): ?>
            <form method="POST" action="<?php echo $baseUrl; ?>/modules/reservations/api.php" class="d-grid gap-2">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="id" value="<?php echo $res['id']; ?>">
                <?php if ($res['status'] === 'pending'): ?>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> <?php echo t('confirm_reservation'); ?></button>
                <?php endif; ?>
            </form>
            <?php endif; ?>

            <?php if ($res['status'] === 'confirmed'): ?>
            <a href="<?php echo $baseUrl; ?>/modules/checkinout/checkin.php?reservation_id=<?php echo $res['id']; ?>" class="btn btn-primary w-100 mb-2">
                <i class="bi bi-box-arrow-in-right"></i> <?php echo t('confirm_checkin'); ?>
            </a>
            <?php endif; ?>

            <?php if (in_array($res['status'], ['pending', 'confirmed'])): ?>
            <a href="<?php echo $baseUrl; ?>/modules/reservations/edit.php?id=<?php echo $res['id']; ?>" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-pencil"></i> <?php echo t('edit'); ?></a>
            <form method="POST" action="<?php echo $baseUrl; ?>/modules/reservations/api.php" onsubmit="return confirm('<?php echo t('confirm_delete'); ?>')">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id" value="<?php echo $res['id']; ?>">
                <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-x-circle"></i> <?php echo t('cancel_reservation'); ?></button>
            </form>
            <?php endif; ?>

            <?php if ($res['status'] === 'checked_out'): ?>
            <a href="<?php echo $baseUrl; ?>/modules/billing/create.php?reservation_id=<?php echo $res['id']; ?>" class="btn btn-success w-100">
                <i class="bi bi-receipt"></i> <?php echo t('generate_bill'); ?>
            </a>
            <?php endif; ?>
        </div></div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
