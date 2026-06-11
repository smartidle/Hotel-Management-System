<?php
require_once __DIR__ . '/../../includes/auth_check.php';

// Admin only
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    setFlash('error', t('access_denied'));
    redirect(getBaseUrl() . '/dashboard.php');
}

$page_title = t('staff_list');
$active_page = 'staff';
$module_js = 'staff';
$baseUrl = getBaseUrl();

$staff = $pdo->query("
    SELECT s.*, r.role_name 
    FROM staff s 
    JOIN roles r ON s.role_id = r.id 
    ORDER BY s.created_at DESC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-person-badge"></i> <?php echo t('staff_list'); ?></h4>
    <a href="<?php echo $baseUrl; ?>/modules/staff/create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> <?php echo t('add_staff'); ?></a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><?php echo t('name'); ?></th>
                        <th><?php echo t('username'); ?></th>
                        <th><?php echo t('email'); ?></th>
                        <th><?php echo t('role'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('last_login'); ?></th>
                        <th><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($staff)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted"><?php echo t('no_data'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($staff as $s): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['username']); ?></td>
                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                        <td><span class="badge <?php echo $s['role_name'] === 'admin' ? 'badge-admin-role' : 'badge-staff-role'; ?>"><?php echo ucfirst($s['role_name']); ?></span></td>
                        <td><span class="badge badge-status <?php echo $s['status'] === 'active' ? 'badge-available' : 'badge-cancelled'; ?>"><?php echo $s['status'] === 'active' ? t('active') : t('inactive'); ?></span></td>
                        <td><?php echo $s['last_login'] ? date('M d, Y H:i', strtotime($s['last_login'])) : '-'; ?></td>
                        <td>
                            <a href="<?php echo $baseUrl; ?>/modules/staff/edit.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" title="<?php echo t('edit'); ?>"><i class="bi bi-pencil"></i></a>
                            <?php if ($s['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-outline-warning btn-toggle-status" 
                                    data-id="<?php echo $s['id']; ?>"
                                    data-url="<?php echo $baseUrl; ?>/modules/staff/api.php"
                                    data-confirm="<?php echo t('toggle_status'); ?>?"
                                    title="<?php echo t('toggle_status'); ?>">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger btn-delete-staff" 
                                    data-id="<?php echo $s['id']; ?>"
                                    data-url="<?php echo $baseUrl; ?>/modules/staff/api.php"
                                    data-confirm="<?php echo t('confirm_delete'); ?>"
                                    title="<?php echo t('delete'); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
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
