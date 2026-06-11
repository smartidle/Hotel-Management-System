<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = t('guest_list');
$active_page = 'guests';
$baseUrl = getBaseUrl();

$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PER_PAGE;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($search) {
    $where = "WHERE (g.first_name LIKE ? OR g.last_name LIKE ? OR g.phone LIKE ? OR g.email LIKE ?)";
    $params = array_fill(0, 4, "%$search%");
}

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM guests g $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['total'];
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT g.*, 
        (SELECT COUNT(*) FROM reservations r WHERE r.guest_id = g.id) as total_reservations,
        (SELECT COUNT(*) FROM check_ins ci WHERE ci.guest_id = g.id) as total_stays
    FROM guests g 
    $where 
    ORDER BY g.created_at DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$guests = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-people"></i> <?php echo t('guest_list'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/guests/create.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> <?php echo t('add_guest'); ?>
    </a>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control form-control-sm" 
                   placeholder="<?php echo t('search_guest'); ?>..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> <?php echo t('search'); ?></button>
            <a href="<?php echo $baseUrl; ?>/modules/guests/index.php" class="btn btn-sm btn-outline-secondary"><?php echo t('reset'); ?></a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo t('name'); ?></th>
                        <th><?php echo t('phone'); ?></th>
                        <th><?php echo t('email'); ?></th>
                        <th><?php echo t('nationality'); ?></th>
                        <th>VIP</th>
                        <th><?php echo t('total_stays'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($guests)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted"><?php echo t('no_data'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($guests as $g): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($g['first_name'] . ' ' . $g['last_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($g['phone'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($g['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($g['nationality'] ?? '-'); ?></td>
                        <td><?php echo $g['vip_status'] ? '<i class="bi bi-star-fill vip-star"></i>' : '-'; ?></td>
                        <td><?php echo $g['total_stays']; ?></td>
                        <td>
                            <a href="<?php echo $baseUrl; ?>/modules/guests/view.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-info" title="<?php echo t('view'); ?>"><i class="bi bi-eye"></i></a>
                            <a href="<?php echo $baseUrl; ?>/modules/guests/edit.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo t('edit'); ?>"><i class="bi bi-pencil"></i></a>
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
        <small class="text-muted"><?php echo t('showing'); ?> <?php echo $offset+1; ?>-<?php echo min($offset+$perPage, $total); ?> <?php echo t('of'); ?> <?php echo $total; ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">&laquo;</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">&raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
