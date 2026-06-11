<?php
require_once __DIR__ . '/../../includes/auth_check.php';

$active_page = 'reservations';
$module_js = 'reservations';

// Build query with filters
$where = ["1=1"];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "r.status = ?";
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where[] = "r.check_in_date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = "r.check_out_date <= ?";
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['search'])) {
    $where[] = "(r.reservation_code LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Count total
$countSql = "SELECT COUNT(*) FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    WHERE {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch reservations with JOIN guests, rooms, room_types
$sql = "SELECT r.*, g.first_name AS guest_first, g.last_name AS guest_last,
    rm.room_number, rt.name AS room_type_name, rt.base_price
    FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    LEFT JOIN room_types rt ON rm.room_type_id = rt.id
    WHERE {$whereClause}
    ORDER BY r.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusBadge = [
    'pending'     => 'bg-warning text-dark',
    'confirmed'   => 'bg-info',
    'checked_in'  => 'bg-primary',
    'checked_out' => 'bg-success',
    'cancelled'   => 'bg-danger',
    'no_show'     => 'bg-secondary',
];

$page_title = t('reservation_list');

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-calendar-check me-2"></i><?= t('reservation_list') ?></h4>
    <a href="create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i><?= t('new_reservation') ?>
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label"><?= t('status') ?></label>
                <select name="status" class="form-select">
                    <option value=""><?= t('all') ?></option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>><?= t('status_pending') ?></option>
                    <option value="confirmed" <?= ($_GET['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>><?= t('status_confirmed') ?></option>
                    <option value="checked_in" <?= ($_GET['status'] ?? '') === 'checked_in' ? 'selected' : '' ?>><?= t('status_checked_in') ?></option>
                    <option value="checked_out" <?= ($_GET['status'] ?? '') === 'checked_out' ? 'selected' : '' ?>><?= t('status_checked_out') ?></option>
                    <option value="cancelled" <?= ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>><?= t('status_cancelled') ?></option>
                    <option value="no_show" <?= ($_GET['status'] ?? '') === 'no_show' ? 'selected' : '' ?>><?= t('status_no_show') ?></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label"><?= t('date_from') ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label"><?= t('date_to') ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label"><?= t('search') ?></label>
                <input type="text" name="search" class="form-control" placeholder="<?= t('reservation_code') ?> / <?= t('guest_name') ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-search me-1"></i><?= t('filter') ?>
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i><?= t('reset') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Reservations Table -->
<div class="card">
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
                        <th><?= t('status') ?></th>
                        <th><?= t('total') ?></th>
                        <th><?= t('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <?= t('no_data') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $r):
                            $badge = $statusBadge[$r['status']] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td>
                                <a href="view.php?id=<?= $r['id'] ?>" class="fw-semibold text-decoration-none">
                                    <?= htmlspecialchars($r['reservation_code']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($r['guest_first'] . ' ' . $r['guest_last']) ?></td>
                            <td>
                                <?= htmlspecialchars($r['room_number']) ?>
                                <small class="text-muted d-block"><?= htmlspecialchars($r['room_type_name'] ?? '') ?></small>
                            </td>
                            <td><?= date('M d, Y', strtotime($r['check_in_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($r['check_out_date'])) ?></td>
                            <td>
                                <span class="badge <?= $badge ?>"><?= t('status_' . $r['status']) ?></span>
                            </td>
                            <td class="fw-semibold"><?= formatCurrency($r['total_amount']) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary" title="<?= t('view') ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                                    <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-outline-secondary" title="<?= t('edit') ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] === 'pending'): ?>
                                    <button class="btn btn-outline-success btn-confirm-reservation" data-id="<?= $r['id'] ?>" title="<?= t('confirm') ?>">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                                    <button class="btn btn-outline-danger btn-cancel-reservation" data-id="<?= $r['id'] ?>" title="<?= t('cancel') ?>">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">
            <?= t('showing') ?> <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalRecords) ?> <?= t('of') ?> <?= $totalRecords ?>
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                </li>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
